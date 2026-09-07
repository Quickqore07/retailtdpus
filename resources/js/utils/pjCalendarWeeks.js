export function generateWeekOptionsForYear(year) {
    const weeks = []
    let cursor = new Date(Date.UTC(year, 0, 1))

    while (cursor.getUTCDay() !== 1) {
        cursor.setUTCDate(cursor.getUTCDate() - 1)
    }

    let index = 1
    while (index <= 54) {
        const start = new Date(cursor)
        const end = new Date(cursor)
        end.setUTCDate(end.getUTCDate() + 6)

        if (start.getUTCFullYear() > year && end.getUTCFullYear() > year) {
            break
        }

        weeks.push({
            value: end.toISOString().slice(0, 10),
            label: `Week ${index} (${formatWeekDate(start)} - ${formatWeekDate(end)})`,
            weekNumber: index,
        })

        cursor.setUTCDate(cursor.getUTCDate() + 7)
        index += 1
    }

    return weeks.filter((week) => week.label.includes(String(year)))
}

export function formatWeekDate(date) {
    const month = String(date.getUTCMonth() + 1).padStart(2, '0')
    const day = String(date.getUTCDate()).padStart(2, '0')
    const year = date.getUTCFullYear()

    return `${month}/${day}/${year}`
}

export function formatWeekLabels(weekValues, year) {
    const options = generateWeekOptionsForYear(Number(year))
    const optionMap = Object.fromEntries(options.map((option) => [option.value, option.label]))

    return (weekValues || []).map((value) => optionMap[value] || value)
}

export function getYearOptions(startOffset = 1, endYear = 2000) {
    const currentYear = new Date().getFullYear()
    const years = []

    for (let year = currentYear + startOffset; year >= endYear; year--) {
        years.push(year)
    }

    return years
}
