export const UPLOAD_MAX_SIZE_BYTES = 10 * 1024 * 1024

export const UPLOAD_MAX_SIZE_MB = 10

export const UPLOAD_MAX_SIZE_NOTE = `Maximum file size: ${UPLOAD_MAX_SIZE_MB}MB`

export const DOCUMENT_MAX_SIZE_BYTES = UPLOAD_MAX_SIZE_BYTES

export const DOCUMENT_MAX_SIZE_MB = UPLOAD_MAX_SIZE_MB

export const DOCUMENT_ACCEPT = '.pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx'

export const DOCUMENT_HELP_TEXT = `PDF, JPG, PNG, DOC, DOCX, XLS, XLSX up to ${DOCUMENT_MAX_SIZE_MB}MB.`

export const DOCUMENT_UPLOAD_NOTE = `Supported formats: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX. ${UPLOAD_MAX_SIZE_NOTE}.`

export const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return `${Math.round((bytes / Math.pow(k, i)) * 100) / 100} ${sizes[i]}`
}

export const validateDocumentFileSize = (file, maxSize = UPLOAD_MAX_SIZE_BYTES) => {
  if (!file) {
    return null
  }

  if (file.size > maxSize) {
    return `File size exceeds ${formatFileSize(maxSize)}. Please upload a smaller file.`
  }

  return null
}

export const rejectOversizedFile = (file, onError) => {
  const error = validateDocumentFileSize(file)
  if (error) {
    onError?.(error)
    return true
  }

  return false
}

export const assignValidatedFile = (file, assign, { onError, input } = {}) => {
  if (!file) {
    assign(null)
    return true
  }

  if (rejectOversizedFile(file, onError)) {
    assign(null)
    if (input) {
      input.value = ''
    }
    return false
  }

  assign(file)
  return true
}
