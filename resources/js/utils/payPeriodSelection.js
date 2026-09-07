const STORAGE_KEY = 'payPeriodSelection'

export function savePayPeriodSelection(year, period) {
    if (year == null || !period) return
    localStorage.setItem(STORAGE_KEY, JSON.stringify({ year, period }))
}

export function getPayPeriodSelection() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY)
        return stored ? JSON.parse(stored) : null
    } catch {
        return null
    }
}

function parsePeriodDates(periodValue) {
    if (!periodValue) return null
    const parts = periodValue.split(/\s+to\s+/i)
    if (parts.length !== 2) return null
    return {
        start: parts[0].trim().split('T')[0],
        end: parts[1].trim().split('T')[0],
    }
}

export function findMatchingPeriod(periods, savedPeriod) {
    if (!savedPeriod || !periods?.length) return null

    const exact = periods.find((p) => p.value === savedPeriod)
    if (exact) return exact

    const savedDates = parsePeriodDates(savedPeriod)
    if (!savedDates) return null

    return periods.find((p) => {
        const start = p.startDate?.toISOString?.().split('T')[0]
        const end = p.endDate?.toISOString?.().split('T')[0]
        return start === savedDates.start && end === savedDates.end
    }) || null
}

export function findCurrentPeriod(periods) {
    if (!periods?.length) return null
    const now = Date.now()
    return periods.find(
        (p) => now >= p.startDate.getTime() && now <= p.endDate.getTime()
    ) || periods[0]
}

export function resolveDefaultYear(availableYears) {
    const stored = getPayPeriodSelection()
    const currentYear = new Date().getFullYear()

    if (stored?.year && availableYears?.includes(stored.year)) {
        return stored.year
    }

    if (availableYears?.includes(currentYear)) {
        return currentYear
    }

    return availableYears?.[0] ?? currentYear
}

export function resolveDefaultPeriod(availablePeriods, savedPeriod = getPayPeriodSelection()?.period) {
    if (!availablePeriods?.length) return ''

    if (savedPeriod) {
        const matched = findMatchingPeriod(availablePeriods, savedPeriod)
        if (matched) return matched.value
    }

    return findCurrentPeriod(availablePeriods)?.value ?? availablePeriods[0].value
}

export function applyPayPeriodDefaults(filters, { availableYears, getAvailablePeriods }) {
    filters.selectedYear = resolveDefaultYear(availableYears)

    const periods = getAvailablePeriods()
    if (periods?.length) {
        filters.selectedPeriod = resolveDefaultPeriod(periods)
    }
}
