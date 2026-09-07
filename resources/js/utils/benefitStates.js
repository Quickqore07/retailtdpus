export const SUPPORTED_BENEFIT_STATES = [
  { id: 'PA', name: 'Pennsylvania' },
  { id: 'DE', name: 'Delaware' },
  { id: 'NJ', name: 'New Jersey' },
  { id: 'MD', name: 'Maryland' },
  { id: 'VA', name: 'Virginia' },
]

const STATE_NAME_TO_CODE = {
  PENNSYLVANIA: 'PA',
  DELAWARE: 'DE',
  'NEW JERSEY': 'NJ',
  MARYLAND: 'MD',
  VIRGINIA: 'VA',
}

export function resolveStateCode(raw) {
  const normalized = (raw || '').trim().toUpperCase()
  if (SUPPORTED_BENEFIT_STATES.some((state) => state.id === normalized)) {
    return normalized
  }
  if (STATE_NAME_TO_CODE[normalized]) {
    return STATE_NAME_TO_CODE[normalized]
  }
  if (normalized.includes('MD')) {
    return 'MD'
  }
  return 'PA'
}

export function getEmployeeRates(employee) {
  if (!employee) return []

  return [
    ...(employee.employee_rates ?? employee.employeeRates ?? []),
    ...(employee.employee_rates_requests ?? employee.employeeRatesRequests ?? []),
  ]
}

export function employeeHasCompany(employee) {
  return getEmployeeRates(employee).some((rate) => rate?.company_id || rate?.company?.id)
}

export function resolveCompanyStateCode(employee) {
  for (const rate of getEmployeeRates(employee)) {
    const company = rate?.company
    if (!company) continue

    const raw = company.state?.name ?? company.state?.code ?? company.state_name ?? ''
    if (raw) {
      return resolveStateCode(raw)
    }
  }

  return ''
}

export function resolveDefaultBenefitsState(employee) {
  if (employee?.benefits_state) {
    return resolveStateCode(employee.benefits_state)
  }

  return resolveCompanyStateCode(employee) || 'PA'
}
