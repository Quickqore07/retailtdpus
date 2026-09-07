/**
 * Normalize SSN to digits-only (handles XXX-XX-XXXX or 9 digits).
 * @param {string|null|undefined} ssn
 * @returns {string}
 */
export function normalizeSSN(ssn) {
    if (ssn == null || typeof ssn !== 'string') return ''
    return String(ssn).replace(/\D/g, '')
}

/**
 * Validate US SSN: exactly 9 digits (with or without dashes).
 * @param {string|null|undefined} ssn
 * @returns {boolean}
 */
export function isSSNValid(ssn) {
    const digits = normalizeSSN(ssn)
    return digits.length === 9
}

/**
 * Format SSN for storage: 9 digits as XXX-XX-XXXX. Returns empty string if invalid/empty.
 * @param {string|null|undefined} ssn
 * @returns {string}
 */
export function formatSSN(ssn) {
    const digits = normalizeSSN(ssn)
    if (digits.length !== 9) return ''
    return `${digits.slice(0, 3)}-${digits.slice(3, 5)}-${digits.slice(5, 9)}`
}

/**
 * Format SSN as user types: digits only, max 9, auto-insert dashes (XXX-XX-XXXX).
 * Use in @input to let users type 9 digits without typing dashes.
 * @param {string|null|undefined} ssn
 * @returns {string}
 */
export function formatSSNAsYouType(ssn) {
    const digits = normalizeSSN(ssn).slice(0, 9)
    if (digits.length <= 3) return digits
    if (digits.length <= 5) return `${digits.slice(0, 3)}-${digits.slice(3)}`
    return `${digits.slice(0, 3)}-${digits.slice(3, 5)}-${digits.slice(5)}`
}

/**
 * Mask SSN for display (show only last 4 digits).
 * @param {string|null|undefined} ssn
 * @returns {string}
 */
export function maskSSN(ssn) {
    if (!ssn) return 'N/A'
    const digits = normalizeSSN(ssn)
    if (digits.length < 4) return digits || 'N/A'
    return `****${digits.slice(-4)}`
}
