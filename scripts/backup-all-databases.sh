#!/usr/bin/env bash
# Backup all MySQL databases the configured user can access.
# Reads DB_* values from the project .env file.
#
# Usage:
#   ./scripts/backup-all-databases.sh
#   ./scripts/backup-all-databases.sh --include-system
#   ./scripts/backup-all-databases.sh --output-dir /path/to/backups

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="${PROJECT_ROOT}/.env"
OUTPUT_DIR=""
INCLUDE_SYSTEM=0
NO_ZIP=0

declare -A PROJECT_MAPPING=(
    [laravelvite]=tdpus
    [kyn-task]=kyn
    [production_quickob]=quickob
)
QQ_DATABASES=(pj)
PHP_BIN=""

file_size() {
    wc -c < "$1" | tr -d '[:space:]'
}

detect_php_bin() {
    local candidate
    for candidate in php8.4 php; do
        if command -v "$candidate" >/dev/null 2>&1; then
            PHP_BIN="$candidate"
            return 0
        fi
    done
    return 1
}

get_env() {
    local key="$1"
    local default="${2:-}"
    if [[ ! -f "$ENV_FILE" ]]; then
        echo "$default"
        return
    fi
    local line
    line="$(grep -E "^[[:space:]]*${key}[[:space:]]*=" "$ENV_FILE" | tail -n 1 || true)"
    if [[ -z "$line" ]]; then
        echo "$default"
        return
    fi
    local value="${line#*=}"
    value="$(echo "$value" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')"
    value="${value%\"}"
    value="${value#\"}"
    value="${value%\'}"
    value="${value#\'}"
    echo "$value"
}

resolve_backup_name() {
    local db="$1"
    if [[ -n "${PROJECT_MAPPING[$db]:-}" ]]; then
        echo "${PROJECT_MAPPING[$db]}"
    else
        echo "$db"
    fi
}

belongs_to_goquickqore_backup() {
    local db="$1"
    if [[ "$db" == qq_* || "$db" == pj_* ]]; then
        return 0
    fi
    local qq_db
    for qq_db in "${QQ_DATABASES[@]}"; do
        if [[ "$db" == "$qq_db" ]]; then
            return 0
        fi
    done
    return 1
}

create_zip_backup() {
    local zip_path="$1"
    local db="$2"
    local sql_file="$3"
    local sql_name
    local staging
    sql_name="$(basename "$sql_file")"

    if command -v zip >/dev/null 2>&1; then
        staging="$(mktemp -d)"
        mkdir -p "${staging}/databases/${db}"
        cp "$sql_file" "${staging}/databases/${db}/${sql_name}"
        if (cd "$staging" && zip -q -9 "$zip_path" databases); then
            rm -rf "$staging"
        else
            rm -rf "$staging"
            return 1
        fi
    elif detect_php_bin; then
        if ! ZIP_PATH="$zip_path" SQL_FILE="$sql_file" DB_NAME="$db" SQL_NAME="$sql_name" "$PHP_BIN" -r '
            $zip = new ZipArchive();
            if ($zip->open(getenv("ZIP_PATH"), ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                exit(1);
            }
            $zip->addFile(getenv("SQL_FILE"), "databases/" . getenv("DB_NAME") . "/" . getenv("SQL_NAME"));
            $zip->close();
        '; then
            return 1
        fi
    else
        echo "Neither zip nor php is available for creating archives." >&2
        return 1
    fi

    [[ -f "$zip_path" ]] && [[ "$(file_size "$zip_path")" -gt 0 ]]
}

format_bytes() {
    local bytes="$1"
    awk -v b="$bytes" 'BEGIN {
        split("B KB MB GB TB", u, " ")
        i = 1
        while (b > 1024 && i < 5) { b /= 1024; i++ }
        printf "%.2f %s", b, u[i]
    }'
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --output-dir)
            OUTPUT_DIR="$2"
            shift 2
            ;;
        --include-system)
            INCLUDE_SYSTEM=1
            shift
            ;;
        --no-zip)
            NO_ZIP=1
            shift
            ;;
        *)
            echo "Unknown option: $1" >&2
            exit 1
            ;;
    esac
done

DB_HOST="$(get_env DB_HOST 127.0.0.1)"
DB_PORT="$(get_env DB_PORT 3306)"
DB_USER="$(get_env DB_USERNAME root)"
DB_PASS="$(get_env DB_PASSWORD "")"
MYSQLDUMP_BIN="$(get_env MYSQLDUMP_BINARY mysqldump)"
MYSQL_BIN="$(get_env MYSQL_BINARY mysql)"

if [[ -z "$OUTPUT_DIR" ]]; then
    OUTPUT_DIR="${PROJECT_ROOT}/storage/app/backups"
fi

mkdir -p "$OUTPUT_DIR"

date_folder="$(date +%Y-%m-%d)"
timestamp="$(date +%Y-%m-%d_%H%M%S)"
work_dir="${OUTPUT_DIR}/all_databases_${timestamp}"
mkdir -p "$work_dir"

mysql_cmd=("$MYSQL_BIN" --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USER" --batch --skip-column-names -e "SHOW DATABASES;")
dump_cmd_base=("$MYSQLDUMP_BIN" --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USER" --single-transaction --quick --routines --triggers --events --default-character-set=utf8mb4 --set-gtid-purged=OFF)

if [[ -n "$DB_PASS" ]]; then
    mysql_cmd=(--password="$DB_PASS" "${mysql_cmd[@]}")
    dump_cmd_base=(--password="$DB_PASS" "${dump_cmd_base[@]}")
fi

echo "Listing databases for user '${DB_USER}' on ${DB_HOST}:${DB_PORT} ..."

mapfile -t all_databases < <("${mysql_cmd[@]}")
system_databases=(information_schema performance_schema mysql sys)
databases=()

for db in "${all_databases[@]}"; do
    db="$(echo "$db" | xargs)"
    [[ -z "$db" ]] && continue
    if [[ "$INCLUDE_SYSTEM" -eq 0 ]]; then
        skip=0
        for sys_db in "${system_databases[@]}"; do
            if [[ "$db" == "$sys_db" ]]; then
                skip=1
                break
            fi
        done
        [[ "$skip" -eq 1 ]] && continue
    fi
    databases+=("$db")
done

if [[ "${#databases[@]}" -eq 0 ]]; then
    echo "No databases found to backup."
    exit 0
fi

echo "Backing up ${#databases[@]} database(s)..."
declare -A dumped_files=()
failed=()

for db in "${databases[@]}"; do
    sql_file="${work_dir}/${db}_${timestamp}.sql"
    echo "  -> ${db}"
    if "${dump_cmd_base[@]}" --result-file="$sql_file" "$db" && [[ -s "$sql_file" ]]; then
        size_bytes="$(file_size "$sql_file")"
        echo "    ok ($(format_bytes "$size_bytes"))"
        dumped_files["$db"]="$sql_file"
    else
        echo "    x dump failed" >&2
        failed+=("$db")
    fi
done

if [[ "${#dumped_files[@]}" -eq 0 ]]; then
    rm -rf "$work_dir"
    echo "All database dumps failed." >&2
    exit 1
fi

if [[ "$NO_ZIP" -eq 1 ]]; then
    echo
    echo "Backup completed (SQL files only)."
    echo "Location: ${work_dir}"
else
    zip_dir="${OUTPUT_DIR}/${date_folder}"
    mkdir -p "$zip_dir"

    goquickqore_files=()
    other_dbs=()
    mapfile -t dumped_dbs < <(printf '%s\n' "${!dumped_files[@]}" | sort)

    for db in "${dumped_dbs[@]}"; do
        if belongs_to_goquickqore_backup "$db"; then
            goquickqore_files+=("$db")
        else
            other_dbs+=("$db")
        fi
    done

    created_count=0

    if [[ "${#goquickqore_files[@]}" -gt 0 ]]; then
        folder_name="goquickqore_${timestamp}"
        folder_path="${zip_dir}/${folder_name}"
        mkdir -p "$folder_path"
        echo "Creating ${folder_name}/ (${#goquickqore_files[@]} database(s))..."
        folder_ok=1
        for db in "${goquickqore_files[@]}"; do
            zip_name="${db}_${timestamp}.zip"
            zip_path="${folder_path}/${zip_name}"
            sql_file="${dumped_files[$db]}"
            if create_zip_backup "$zip_path" "$db" "$sql_file"; then
                size_bytes="$(file_size "$zip_path")"
                echo "  ok ${db} ($(format_bytes "$size_bytes"))"
            else
                echo "  x failed to create ${zip_name}" >&2
                folder_ok=0
            fi
        done
        if [[ "$folder_ok" -eq 1 ]]; then
            created_count=$((created_count + 1))
        fi
    fi

    for db in "${other_dbs[@]}"; do
        backup_name="$(resolve_backup_name "$db")"
        zip_name="${backup_name}_${timestamp}.zip"
        zip_path="${zip_dir}/${zip_name}"
        sql_file="${dumped_files[$db]}"
        echo "Creating ${zip_name} (1 database(s))..."
        if create_zip_backup "$zip_path" "$db" "$sql_file"; then
            size_bytes="$(file_size "$zip_path")"
            echo "  ok ($(format_bytes "$size_bytes"))"
            created_count=$((created_count + 1))
        else
            echo "  x failed to create ${zip_name}" >&2
        fi
    done

    rm -rf "$work_dir"

    if [[ "$created_count" -eq 0 ]]; then
        echo "No backup files were created." >&2
        exit 1
    fi

    echo
    echo "Backup completed successfully!"
    echo "Date folder: ${date_folder}"
    echo "Backup items: ${created_count}"
    echo "Location: ${zip_dir}"
fi

if [[ "${#failed[@]}" -gt 0 ]]; then
    echo
    echo "Failed databases: $(IFS=', '; echo "${failed[*]}")"
    exit 1
fi
