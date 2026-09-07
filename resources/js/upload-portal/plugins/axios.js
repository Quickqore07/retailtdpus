/**
 * Upload Portal API Service
 * Axios instance with interceptors for session-based authentication
 */

import axios from 'axios'

// Create axios instance with default config
const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || '/',
    timeout: 30000,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true, // Required for Laravel session authentication
})

/**
 * Request Interceptor
 * - Adds CSRF token to requests
 * - Logs requests in development mode
 */
api.interceptors.request.use(
    (config) => {
        // Add CSRF token for Laravel
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        if (csrfToken && config.headers) {
            config.headers['X-CSRF-TOKEN'] = csrfToken
        }

        // Log request in development
        if (import.meta.env.DEV) {
            // console.log(`🚀 [Upload Portal API] ${config.method?.toUpperCase()} ${config.url}`)
        }

        return config
    },
    (error) => {
        // console.error('❌ [Request Error]', error)
        return Promise.reject(error)
    }
)

/**
 * Response Interceptor
 * - Handles successful responses
 * - Handles errors globally (401, 403, 422, 500, etc.)
 */
api.interceptors.response.use(
    (response) => {
        // Log response in development
        if (import.meta.env.DEV) {
            // console.log(`✅ [API Response] ${response.config.url}`, response.data)
        }
        return response
    },
    (error) => {
        // Log error in development
        if (import.meta.env.DEV) {
            // console.error(`❌ [API Error] ${error.response?.status}`, error.response?.data || error.message)
        }

        // Handle specific error statuses
        switch (error.response?.status) {
            case 401:
                // Unauthorized - Session expired or not authenticated
                // console.error('Session expired. Redirecting to login...')
                // Redirect to upload portal login
                window.location.href = '/upload-portal/login'
                break

            case 403:
                // Forbidden - User doesn't have permission
                console.error('Access forbidden. You do not have permission to perform this action.')
                break

            case 404:
                // Not Found
                console.error('Resource not found.')
                break

            case 422:
                // Validation Error - Laravel returns validation errors with 422
                console.error('Validation failed:', error.response?.data?.errors)
                break

            case 429:
                // Too Many Requests - Rate limiting
                console.error('Too many requests. Please try again later.')
                break

            case 500:
            case 502:
            case 503:
                // Server Error
                console.error('Server error. Please try again later.')
                break

            default:
                // Network error or unknown error
                if (!error.response) {
                    console.error('Network error. Please check your internet connection.')
                }
                break
        }

        return Promise.reject(error)
    }
)

export default api