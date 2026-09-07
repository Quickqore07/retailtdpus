import json
import os
import re
import sys
from datetime import date, datetime

try:
    import pdfplumber
except ImportError:
    print('Missing dependency: pdfplumber. Install with: pip install pdfplumber')
    sys.exit(1)

MONEY_RE = re.compile(r'-?\$?\s*[\d,]+\.\d{2}')
DATE_RE = re.compile(r'(\d{1,2}[/-]\d{1,2}[/-]\d{2,4})')
PO_RANGE_RE = re.compile(
    r'(?P<label>Roy|Mktg)\s*(?P<start>\d{1,2}[/-]\d{1,2})\s*[-–]\s*(?P<end>\d{1,2}[/-]\d{1,2})',
    re.IGNORECASE,
)


def empty_invoice():
    return {
        'invoice_number': '',
        'invoice_date': '',
        'due_date': '',
        'amount': 0.0,
        'store_number': '',
        'bill_to': '',
        'description': '',
        'purchase_order': '',
        'type': '',
    }


def parse_money(value):
    if value is None:
        return 0.0
    text = str(value).strip()
    if not text or text in ('-', '--'):
        return 0.0
    negative = '(' in text and ')' in text
    text = text.replace('$', '').replace(',', '').replace('(', '').replace(')', '').strip()
    try:
        amount = float(text)
        return -amount if negative else amount
    except ValueError:
        return 0.0


def normalize_store_number(value):
    digits = re.sub(r'\D', '', str(value or ''))
    if not digits:
        return ''
    stripped = digits.lstrip('0')
    return stripped if stripped else '0'


def normalize_invoice_number(value):
    text = str(value or '').strip()
    if re.fullmatch(r'0+\d+', text):
        stripped = text.lstrip('0')
        return stripped or '0'
    return text


def normalize_date(value):
    text = str(value or '').strip()
    if not text:
        return ''
    for fmt in ('%m/%d/%Y', '%m-%d-%Y', '%m/%d/%y', '%m-%d-%y', '%Y-%m-%d', '%d/%m/%Y'):
        try:
            return datetime.strptime(text, fmt).strftime('%Y-%m-%d')
        except ValueError:
            continue
    return text


def parse_month_day(value):
    text = str(value or '').strip()
    for fmt in ('%m/%d', '%m-%d'):
        try:
            parsed = datetime.strptime(text, fmt)
            return parsed.month, parsed.day
        except ValueError:
            continue

    for sep in ('/', '-'):
        if sep not in text:
            continue
        parts = text.split(sep)
        if len(parts) == 2 and parts[0].isdigit() and parts[1].isdigit():
            month, day = int(parts[0]), int(parts[1])
            if 1 <= month <= 12 and 1 <= day <= 31:
                return month, day
    return None


def resolve_po_range_dates(start_md, end_md, reference=None):
    """Assign years to M/D range; invoice date is the range end.

    Example: Roy 12/29 - 1/29 with reference 2027-01-30
    -> start 2026-12-29, end (invoice date) 2027-01-29
    """
    ref = reference or date.today()
    start_month, start_day = start_md
    end_month, end_day = end_md
    crosses_year = (start_month, start_day) > (end_month, end_day)

    try:
        end_this_year = date(ref.year, end_month, end_day)
    except ValueError:
        return None, None

    if end_this_year <= ref:
        end_year = ref.year
    else:
        end_year = ref.year - 1

    start_year = end_year - 1 if crosses_year else end_year

    try:
        start_date = date(start_year, start_month, start_day)
        end_date = date(end_year, end_month, end_day)
    except ValueError:
        return None, None

    return start_date, end_date


def invoice_date_from_purchase_order(purchase_order, reference=None):
    match = PO_RANGE_RE.search(str(purchase_order or ''))
    if not match:
        return ''

    start_md = parse_month_day(match.group('start'))
    end_md = parse_month_day(match.group('end'))
    if not start_md or not end_md:
        return ''

    _start, end_date = resolve_po_range_dates(start_md, end_md, reference)
    if not end_date:
        return ''

    return end_date.strftime('%Y-%m-%d')


def first_match(text, patterns):
    for pattern in patterns:
        match = re.search(pattern, text, re.IGNORECASE | re.MULTILINE)
        if match:
            return match.group(1).strip()
    return ''


STORE_LINE_RE = re.compile(r'^(?P<name>.+?)\s+[-–]\s*(?P<store>0*\d+)\b')


def parse_bill_to_store_line(line):
    match = STORE_LINE_RE.match(str(line or '').strip())
    if not match:
        return None, None
    return match.group('name').strip(), normalize_store_number(match.group('store'))


def extract_store_number(text, invoice_data):
    lines = [line.strip() for line in text.split('\n') if line.strip()]

    for index, line in enumerate(lines):
        bill_to = re.match(r'bill\s*to\s*:?\s*(.*)$', line, re.IGNORECASE)
        if not bill_to:
            continue

        same_line = bill_to.group(1).strip()
        candidates = []
        if same_line:
            candidates.append(same_line)
        if index + 1 < len(lines):
            candidates.append(lines[index + 1])

        for candidate in candidates:
            name, store = parse_bill_to_store_line(candidate)
            if store:
                invoice_data['store_number'] = store
                invoice_data['bill_to'] = candidate
                return
        return


def extract_invoice_fields(text, invoice_data):
    invoice_data['invoice_number'] = normalize_invoice_number(first_match(text, [
        r'invoice\s*(?:number|no\.?|#)\s*[:#]?\s*([A-Za-z0-9][A-Za-z0-9\-\/]*)',
        r'\bInvoice\s+(\d{4,})',
        r'\binv(?:oice)?\s*[:#]\s*([A-Za-z0-9][A-Za-z0-9\-\/]*)',
        r'document\s*(?:number|no\.?|#)\s*[:#]?\s*([A-Za-z0-9][A-Za-z0-9\-\/]*)',
    ]))

    invoice_data['invoice_date'] = normalize_date(first_match(text, [
        r'invoice\s*date\s*[:#]?\s*' + DATE_RE.pattern,
        r'bill\s*date\s*[:#]?\s*' + DATE_RE.pattern,
        r'(?<!due\s)(?<!payment\s)\bdate\s*[:#]?\s*' + DATE_RE.pattern,
    ]))

    invoice_data['due_date'] = normalize_date(first_match(text, [
        r'due\s*date\s*[:#]?\s*' + DATE_RE.pattern,
        r'payment\s*due\s*[:#]?\s*' + DATE_RE.pattern,
        r'\bdue\s*[:#]?\s*' + DATE_RE.pattern,
    ]))

    amount_text = first_match(text, [
        r'invoice\s*subtotal\s*[:#]?\s*\$?\s*([\d,]+\.\d{2})',
        r'(?:invoice\s*)?total\s*(?:due)?\s*[:#]?\s*\$?\s*([\d,]+\.\d{2})',
        r'amount\s*due\s*[:#]?\s*\$?\s*([\d,]+\.\d{2})',
        r'balance\s*due\s*[:#]?\s*\$?\s*([\d,]+\.\d{2})',
        r'total\s*amount\s*[:#]?\s*\$?\s*([\d,]+\.\d{2})',
        r'\bamount\s*[:#]?\s*\$?\s*([\d,]+\.\d{2})',
    ])
    invoice_data['amount'] = parse_money(amount_text)

    if invoice_data['amount'] == 0:
        amounts = MONEY_RE.findall(text)
        if amounts:
            invoice_data['amount'] = parse_money(amounts[-1])

    invoice_data['purchase_order'] = first_match(text, [
        r'purchase\s*order\s*(?:number|no\.?|#)?\s*[:#]?\s*([^\n]+)',
        r'\bP\.?\s*O\.?\s*(?:number|no\.?|#)?\s*[:#]?\s*([^\n]+)',
        r'\bPO\s*(?:number|no\.?|#)\s*[:#]?\s*([^\n]+)',
        r'customer\s*p\.?\s*o\.?\s*[:#]?\s*([^\n]+)',
    ]).strip()

    extract_store_number(text, invoice_data)
    extract_line_item_details(text, invoice_data)


def clean_table_description(value):
    lines = [re.sub(r'\s+', ' ', line).strip() for line in re.split(r'[\n\r]+', str(value or ''))]
    for line in lines:
        if not line:
            continue
        if re.match(r'(description|desc|qty|uom|price|extended|ship\s*to)\b', line, re.IGNORECASE):
            continue
        if re.match(r'store\s*number\s*:', line, re.IGNORECASE):
            continue
        return line
    return ''


def extract_line_item_details(text, invoice_data):
    store_match = re.search(r'store\s*number\s*:\s*(0*\d+)', text, re.IGNORECASE)
    if store_match and not invoice_data['store_number']:
        invoice_data['store_number'] = normalize_store_number(store_match.group(1))

    subtotal = first_match(text, [
        r'invoice\s*subtotal\s*[:#]?\s*\$?\s*([\d,]+\.\d{2})',
        r'\bsubtotal\s*[:#]?\s*\$?\s*([\d,]+\.\d{2})',
    ])
    if subtotal:
        invoice_data['amount'] = parse_money(subtotal)

    descriptions = []
    line_item_re = re.compile(
        r'^\s*\d+\s+(?P<desc>[A-Za-z][A-Za-z0-9 /&.,\'-]+?)\s+[\d,]+\.\d{2,4}\s+[A-Za-z]{1,6}\s+[\d,]+\.\d{2,4}\s+(?P<extended>[\d,]+\.\d{2})\s*$'
    )
    skip_re = re.compile(
        r'^(line|adj|description|qty|uom|price|extended|ship\s*to|store\s*number|invoice\s*subtotal|united states)\b',
        re.IGNORECASE
    )

    in_items = False
    for raw in text.split('\n'):
        line = re.sub(r'\s+', ' ', raw).strip()
        if not line:
            continue
        if re.search(r'\bdescription\b', line, re.IGNORECASE) and re.search(r'\bqty\b', line, re.IGNORECASE):
            in_items = True
            continue
        if re.search(r'invoice\s*subtotal|total\s*due|amount\s*due', line, re.IGNORECASE):
            in_items = False
            continue

        item = line_item_re.match(line)
        if item:
            desc = re.sub(r'\s+', ' ', item.group('desc')).strip()
            if desc and not skip_re.match(desc):
                descriptions.append(desc)
            if not invoice_data['amount']:
                invoice_data['amount'] = parse_money(item.group('extended'))
            continue

        if in_items and not skip_re.match(line) and not re.search(r'store\s*number\s*:', line, re.IGNORECASE):
            if re.match(r'^\d+$', line):
                continue
            if re.search(r'\b(rd|road|st|street|ave|avenue|blvd|pike|dr|drive|ln|lane|ct|way|pkwy)\b', line, re.IGNORECASE):
                continue
            if re.search(r',\s*[A-Z]{2}\s+\d{5}', line):
                continue
            if re.search(r'[A-Za-z]{3,}', line) and not re.search(r'\d{5}', line):
                descriptions.append(line)

    if descriptions:
        invoice_data['description'] = descriptions[0]


def fill_from_tables(pdf, invoice_data):
    label_map = {
        'invoice number': 'invoice_number',
        'invoice no': 'invoice_number',
        'invoice #': 'invoice_number',
        'invoice date': 'invoice_date',
        'due date': 'due_date',
        'amount due': 'amount',
        'total due': 'amount',
        'invoice total': 'amount',
        'total': 'amount',
        'description': 'description',
        'purchase order': 'purchase_order',
        'purchase order no': 'purchase_order',
        'purchase order number': 'purchase_order',
        'po number': 'purchase_order',
        'po #': 'purchase_order',
        'p.o. number': 'purchase_order',
        'customer po': 'purchase_order',
    }
    descriptions = []

    for page in pdf.pages:
        for table in (page.extract_tables() or []):
            if not table:
                continue
            header = [re.sub(r'\s+', ' ', str(c or '')).strip().lower() for c in table[0]]
            desc_idx = next((i for i, heading in enumerate(header) if 'description' in heading), None)
            for row_index, row in enumerate(table):
                cells = [re.sub(r'\s+', ' ', str(c or '')).strip() for c in row]
                if desc_idx is not None and row_index > 0 and desc_idx < len(cells):
                    desc = cells[desc_idx]
                    cleaned = clean_table_description(desc)
                    if cleaned:
                        descriptions.append(cleaned)
                for index, cell in enumerate(cells):
                    key = label_map.get(cell.lower().rstrip(':'))
                    if not key or index + 1 >= len(cells) or not cells[index + 1]:
                        continue
                    value = cells[index + 1]
                    if key == 'amount' and not invoice_data['amount']:
                        invoice_data['amount'] = parse_money(value)
                    elif key == 'invoice_date' and not invoice_data['invoice_date']:
                        invoice_data['invoice_date'] = normalize_date(value)
                    elif key == 'due_date' and not invoice_data['due_date']:
                        invoice_data['due_date'] = normalize_date(value)
                    elif key == 'invoice_number' and not invoice_data['invoice_number']:
                        invoice_data['invoice_number'] = normalize_invoice_number(value)
                    elif key == 'purchase_order' and not invoice_data['purchase_order']:
                        invoice_data['purchase_order'] = value
                    elif key == 'description' and not invoice_data['description']:
                        cleaned = clean_table_description(value)
                        if cleaned:
                            invoice_data['description'] = cleaned

    if descriptions and not invoice_data['description']:
        invoice_data['description'] = descriptions[0]


def resolve_invoice_type(invoice_data):
    purchase_order = str(invoice_data.get('purchase_order') or '').strip()
    po_lower = purchase_order.lower()
    if po_lower.startswith('roy'):
        invoice_data['type'] = 'Royalty'
        return
    if po_lower.startswith('mktg'):
        invoice_data['type'] = 'Advertisement'
        return

    description = str(invoice_data.get('description') or '').lower()
    if 'marketing fund' in description:
        invoice_data['type'] = 'Advertisement'
        return
    if 'royalt' in description:
        invoice_data['type'] = 'Royalty'


def extract_text_from_pdf(pdf):
    text = ''
    for page in pdf.pages:
        page_text = page.extract_text() or ''
        if page_text:
            text += page_text + '\n'
    return text


def ocr(pdf_path):
    pdf_path = os.path.abspath(pdf_path)
    upload_dir = os.path.dirname(pdf_path)
    os.makedirs(upload_dir, exist_ok=True)

    file_name = os.path.splitext(os.path.basename(pdf_path))[0]
    directory = os.path.join(upload_dir, file_name)
    os.makedirs(directory, exist_ok=True)

    invoice_data = empty_invoice()
    extracted_text = ''

    try:
        with pdfplumber.open(pdf_path) as pdf:
            extracted_text = extract_text_from_pdf(pdf)
            if extracted_text.strip():
                extract_invoice_fields(extracted_text, invoice_data)
            else:
                print(f'Warning: No text extracted from PDF: {pdf_path}')
            fill_from_tables(pdf, invoice_data)
            invoice_data['invoice_number'] = normalize_invoice_number(invoice_data.get('invoice_number'))
            resolve_invoice_type(invoice_data)
            po_invoice_date = invoice_date_from_purchase_order(invoice_data.get('purchase_order'))
            if po_invoice_date:
                invoice_data['invoice_date'] = po_invoice_date
    except Exception as e:
        print(f'Error extracting text from PDF: {e}')
        import traceback
        traceback.print_exc()
        sys.exit(1)

    try:
        with open(os.path.join(directory, 'raw_text.txt'), 'w', encoding='utf-8') as f:
            f.write(extracted_text)
    except Exception as e:
        print(f'Error saving raw text: {e}')

    json_file = os.path.join(directory, 'invoice_data.json')
    try:
        with open(json_file, 'w', encoding='utf-8') as f:
            json.dump(invoice_data, f, indent=2)
        print(f'JSON file created: {json_file}')
    except Exception as e:
        print(f'Error saving JSON file: {e}')
        import traceback
        traceback.print_exc()
        sys.exit(1)

    print('__INVOICE_JSON__')
    print(json.dumps(invoice_data))
    print(f'Data saved in folder: {directory}')
    return invoice_data


if __name__ == '__main__':
    if len(sys.argv) <= 2:
        print('Usage: python royaltyFeeImport.py ocr <pdf_path>')
        sys.exit(1)

    function = sys.argv[1]
    if function == 'ocr':
        ocr(sys.argv[2])
    else:
        print(f'Unknown function: {function}')
        sys.exit(1)
