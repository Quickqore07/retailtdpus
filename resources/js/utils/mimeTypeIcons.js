/**
 * MIME Type to Icon Mapping Configuration
 * 
 * This file provides a centralized mapping of MIME types to their corresponding SVG icons.
 * Icons should be placed in resources/svg/ directory.
 */

export const mimeTypeIconMap = {
  // PDF Documents
  'application/pdf': 'file-pdf',
  
  // Word Documents
  'application/msword': 'file-doc',
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'file-doc',
  'application/vnd.oasis.opendocument.text': 'file-doc',
  
  // Excel/Spreadsheets
  'application/vnd.ms-excel': 'file-spreadsheet',
  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'file-spreadsheet',
  'application/vnd.oasis.opendocument.spreadsheet': 'file-spreadsheet',
  'text/csv': 'file-spreadsheet',
  
  // Images
  'image/jpeg': 'file-image',
  'image/jpg': 'file-image',
  'image/png': 'file-image',
  'image/gif': 'file-image',
  'image/webp': 'file-image',
  'image/svg+xml': 'file-image',
  'image/bmp': 'file-image',
  'image/tiff': 'file-image',
  
  // Text Files
  'text/plain': 'file-text',
  'text/html': 'file-text',
  'text/css': 'file-text',
  'text/javascript': 'file-text',
  'application/json': 'file-text',
  'application/xml': 'file-text',
  'text/xml': 'file-text',
  
  // Archives/Compressed
  'application/zip': 'file-zip',
  'application/x-zip-compressed': 'file-zip',
  'application/x-rar-compressed': 'file-zip',
  'application/x-7z-compressed': 'file-zip',
  'application/x-tar': 'file-zip',
  'application/gzip': 'file-zip',
  
  // Video
  'video/mp4': 'file-video',
  'video/mpeg': 'file-video',
  'video/quicktime': 'file-video',
  'video/x-msvideo': 'file-video',
  'video/x-flv': 'file-video',
  'video/webm': 'file-video',
  
  // Audio
  'audio/mpeg': 'file-audio',
  'audio/mp3': 'file-audio',
  'audio/wav': 'file-audio',
  'audio/ogg': 'file-audio',
  'audio/webm': 'file-audio',
  
  // Code Files
  'text/x-python': 'file-code',
  'text/x-java': 'file-code',
  'text/x-c': 'file-code',
  'text/x-php': 'file-code',
  'application/x-httpd-php': 'file-code',
  'application/x-sh': 'file-code',
}

/**
 * Color mapping for different file type categories
 * Returns Tailwind gradient classes for the icon background
 */
export const mimeTypeColorMap = {
  'file-pdf': 'from-red-500 to-red-600',
  'file-doc': 'from-blue-500 to-blue-600',
  'file-spreadsheet': 'from-green-500 to-green-600',
  'file-image': 'from-purple-500 to-purple-600',
  'file-text': 'from-gray-500 to-gray-600',
  'file-zip': 'from-yellow-500 to-yellow-600',
  'file-video': 'from-pink-500 to-pink-600',
  'file-audio': 'from-indigo-500 to-indigo-600',
  'file-code': 'from-emerald-500 to-emerald-600',
  'file-default': 'from-slate-500 to-slate-600',
}

/**
 * Get icon name for a given MIME type
 * @param {string} mimeType - The MIME type of the file
 * @returns {string} - The icon name (without .svg extension)
 */
export function getIconForMimeType(mimeType) {
  if (!mimeType) {
    return 'file-default'
  }
  
  // Direct match
  if (mimeTypeIconMap[mimeType]) {
    return mimeTypeIconMap[mimeType]
  }
  
  // Fallback to category-based matching
  const [category] = mimeType.split('/')
  
  switch (category) {
    case 'image':
      return 'file-image'
    case 'video':
      return 'file-video'
    case 'audio':
      return 'file-audio'
    case 'text':
      return 'file-text'
    default:
      return 'file-default'
  }
}

/**
 * Get color gradient classes for a given MIME type
 * @param {string} mimeType - The MIME type of the file
 * @returns {string} - Tailwind gradient classes
 */
export function getColorForMimeType(mimeType) {
  const iconName = getIconForMimeType(mimeType)
  return mimeTypeColorMap[iconName] || mimeTypeColorMap['file-default']
}
