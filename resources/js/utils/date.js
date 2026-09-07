/**
 * Format a date string to a readable format
 * @param {string} dateString - The date string to format
 * @param {string} locale - The locale to use for formatting (default: 'en-US')
 * @returns {string} Formatted date string or '-' if no date provided
 */
export const formatDate = (
  dateString,
  locale = 'en-US',
  timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone
) => {
  if (!dateString) return '-'
  dateString = dateString.includes('T') ? dateString.split('T')[0] : dateString.split(' ')[0]
  const [year, month, day] = dateString.split('-')
  const date = new Date(year, month - 1, day)

  return date.toLocaleDateString(locale, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    timeZone
  })
}

/**
 * Format a date string to include time
 * @param {string} dateString - The date string to format
 * @param {string} locale - The locale to use for formatting (default: 'en-US')
 * @returns {string} Formatted date and time string or '-' if no date provided
 */
export const formatDateTime = (dateString, locale = 'en-US') => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleString(locale, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

/**
 * Format a date string to a relative time (e.g., "2 days ago")
 * @param {string} dateString - The date string to format
 * @returns {string} Relative time string or '-' if no date provided
 */
export const formatRelativeDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now - date
  const diffSec = Math.floor(diffMs / 1000)
  const diffMin = Math.floor(diffSec / 60)
  const diffHour = Math.floor(diffMin / 60)
  const diffDay = Math.floor(diffHour / 24)
  const diffWeek = Math.floor(diffDay / 7)
  const diffMonth = Math.floor(diffDay / 30)
  const diffYear = Math.floor(diffDay / 365)

  if (diffSec < 60) return 'just now'
  if (diffMin < 60) return `${diffMin} minute${diffMin > 1 ? 's' : ''} ago`
  if (diffHour < 24) return `${diffHour} hour${diffHour > 1 ? 's' : ''} ago`
  if (diffDay < 7) return `${diffDay} day${diffDay > 1 ? 's' : ''} ago`
  if (diffWeek < 4) return `${diffWeek} week${diffWeek > 1 ? 's' : ''} ago`
  if (diffMonth < 12) return `${diffMonth} month${diffMonth > 1 ? 's' : ''} ago`
  return `${diffYear} year${diffYear > 1 ? 's' : ''} ago`
}


export const mmddyyyyToYmd = (date) => {
  const [m,d,y] = date.split('/');
  return `${y}-${m.padStart(2,'0')}-${d.padStart(2,'0')}`;
}

export const addDaysToDateString = (dateString, days) => {
  if (!dateString || days === null || days === undefined || days === '') {
    return ''
  }

  const normalized = dateString.includes('T') ? dateString.split('T')[0] : dateString.split(' ')[0]
  const [year, month, day] = normalized.split('-').map(Number)

  if (!year || !month || !day) {
    return ''
  }

  const date = new Date(year, month - 1, day)
  date.setDate(date.getDate() + Number(days))

  const nextYear = date.getFullYear()
  const nextMonth = String(date.getMonth() + 1).padStart(2, '0')
  const nextDay = String(date.getDate()).padStart(2, '0')

  return `${nextYear}-${nextMonth}-${nextDay}`
}

export const formatDateShort = (dateString) => {
  if (!dateString) return '-'
  dateString = dateString.includes('T') ? dateString.split('T')[0] : dateString.split(' ')[0]
  const [year, month, day] = dateString.split('-')
  return `${month}/${day}`
}