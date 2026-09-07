<style>
    @page {
        margin: 28px 36px 32px 36px;
    }

    * {
        box-sizing: border-box;
    }

    body {
        font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
        font-size: 9.5px;
        color: #1a1a1a;
        margin: 0;
        padding: 0;
        line-height: 1.35;
    }

    .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 18px;
    }

    .header-table td {
        vertical-align: top;
        padding: 0;
    }

    .from-name {
        font-size: 11px;
        font-weight: bold;
        letter-spacing: 0.02em;
        margin: 0 0 2px 0;
    }

    .from-dba {
        font-size: 9.5px;
        font-weight: bold;
        margin: 0 0 6px 0;
        color: #333;
    }

    .from-line {
        margin: 0;
        color: #333;
    }

    .statement-title {
        font-size: 22px;
        font-weight: bold;
        letter-spacing: 0.04em;
        margin: 0 0 10px 0;
        text-align: right;
        color: #111;
    }

    .date-label-cell {
        text-align: right;
    }

    .date-label {
        display: inline-block;
        border: 1px solid #bbb;
        background: #f7f7f7;
        padding: 3px 14px;
        font-size: 8.5px;
        font-weight: bold;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .statement-date {
        text-align: right;
        font-size: 10.5px;
        margin-top: 4px;
        font-weight: bold;
    }

    .to-wrap {
        margin-bottom: 14px;
    }

    .to-label {
        display: inline-block;
        border: 1px solid #bbb;
        background: #f7f7f7;
        padding: 2px 10px;
        font-size: 8.5px;
        font-weight: bold;
        letter-spacing: 0.08em;
        margin-bottom: 6px;
    }

    .to-lines {
        margin: 0;
        padding-left: 0;
    }

    .to-lines p {
        margin: 0 0 1px 0;
    }

    .table-note {
        border: 1px solid #c8c8c8;
        background: #fafafa;
        padding: 5px 10px;
        font-size: 8.5px;
        margin-bottom: 0;
        color: #333;
    }

    table.lines {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0;
        border: 1px solid #999;
        border-top: none;
    }

    table.lines th,
    table.lines td {
        border: 1px solid #999;
        padding: 5px 7px;
        vertical-align: top;
    }

    table.lines th {
        background: #e8e8e8;
        font-weight: bold;
        font-size: 8.5px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #222;
    }

    table.lines tbody tr:nth-child(even) td {
        background: #fafafa;
    }

    table.lines td.num {
        text-align: right;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }

    table.lines td.date {
        white-space: nowrap;
        width: 76px;
    }

    table.lines td.trans {
        word-wrap: break-word;
    }

    .meta-row {
        margin-top: 12px;
        font-size: 8.5px;
        color: #444;
    }

    .meta-row strong {
        color: #111;
    }

    .footer-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 22px;
    }

    .footer-table td {
        vertical-align: bottom;
        padding: 0;
    }

    .footer-contact {
        font-size: 8.5px;
        color: #444;
        line-height: 1.45;
        padding-right: 16px;
        max-width: 62%;
    }

    .summary-wrap {
        text-align: right;
    }

    table.summary {
        border-collapse: collapse;
        border: 1px solid #999;
        width: 200px;
        margin-left: auto;
        margin-right: 0;
    }

    table.summary td {
        border: 1px solid #999;
        padding: 6px 10px;
        font-size: 9px;
    }

    table.summary td.label {
        background: #f0f0f0;
        font-weight: bold;
        text-align: left;
    }

    table.summary td.value {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .balance-due {
        font-weight: bold;
        font-size: 10px;
    }

    .gen-line {
        margin-top: 14px;
        font-size: 7.5px;
        color: #888;
        text-align: right;
    }

    .statement-page {
        page-break-after: always;
    }

    .statement-page:last-child {
        page-break-after: auto;
    }
</style>
