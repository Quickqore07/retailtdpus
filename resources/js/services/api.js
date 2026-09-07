/**
 * Unified API Service
 * Consolidated axios instance with interceptors and composable functions
 */

import axios from 'axios'
import { ref } from 'vue'

// Create axios instance with default config
const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || '/api',
    timeout: 30000,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true, // Required for Laravel Sanctum
})

/**
 * Request Interceptor
 * - Adds authentication token to requests
 * - Can modify request config before sending
 */
api.interceptors.request.use(
    (config) => {
        // Get token from localStorage (or your preferred storage)
        const token = localStorage.getItem('auth_token')
        
        if (token && config.headers) {
            config.headers.Authorization = `Bearer ${token}`
        }

        // Add CSRF token for Laravel (if using Sanctum with cookies)
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        if (csrfToken && config.headers) {
            config.headers['X-CSRF-TOKEN'] = csrfToken
        }

        // Log request in development
        if (import.meta.env.DEV) {
            // console.log(`🚀 [API Request] ${config.method?.toUpperCase()} ${config.url}`, config.data || '')
        }

        return config
    },
    (error) => {
        console.error('❌ [API Request Error]', error)
        return Promise.reject(error)
    }
)

/**
 * Response Interceptor
 * - Handles successful responses
 * - Handles errors globally (401, 403, 500, etc.)
 * - Can refresh tokens on 401
 */
api.interceptors.response.use(
    (response) => {
        // Log response in development
        if (import.meta.env.DEV) {
            // console.log(`✅ [API Response] ${response.config.method?.toUpperCase()} ${response.config.url}`, response.data)
        }

        return response
    },
    async (error) => {
        const originalRequest = error.config

        // Log error in development
        if (import.meta.env.DEV) {
            console.error(`❌ [API Error] ${error.response?.status}`, error.response?.data || error.message)
        }

        // Handle specific error statuses
        switch (error.response?.status) {
            case 401:
                // Unauthorized - Token expired or invalid
                if (!originalRequest._retry) {
                    originalRequest._retry = true
                    
                    // Option 1: Try to refresh token
                    // const newToken = await refreshToken()
                    // if (newToken) {
                    //     localStorage.setItem('auth_token', newToken)
                    //     originalRequest.headers.Authorization = `Bearer ${newToken}`
                    //     return api(originalRequest)
                    // }

                    // Option 2: Redirect to login
                    localStorage.removeItem('auth_token')
                    window.location.href = '/'
                }
                break

            case 403:
                // Forbidden - User doesn't have permission
                console.error('Access forbidden. You do not have permission to perform this action.')
                await axios.get('/logout')
                window.location.href = '/'
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

// Export the configured instance as default
export default api

// Export individual HTTP methods for convenience
export const apiGet = (url, config = {}) => 
    api.get(url, config)

export const apiPost = (url, data = {}, config = {}) => 
    api.post(url, data, config)

export const apiPut = (url, data = {}, config = {}) => 
    api.put(url, data, config)

export const apiPatch = (url, data = {}, config = {}) => 
    api.patch(url, data, config)

export const apiDelete = (url, config = {}) => 
    api.delete(url, config)

/**
 * Composable for making API calls with reactive state
 * Returns reactive refs for data, error, loading and an execute function
 */
export function useApi(method, url, payload = null, options = {}) {
    const data = ref(null)
    const error = ref(null)
    const loading = ref(false)

    const execute = async () => {
        loading.value = true
        error.value = null

        try {
            let response
            switch (method) {
                case 'get':
                    response = await apiGet(url, payload)
                    break
                case 'post':
                    response = await apiPost(url, payload)
                    break
                case 'put':
                    response = await apiPut(url, payload)
                    break
                case 'patch':
                    response = await apiPatch(url, payload)
                    break
                case 'delete':
                    response = await apiDelete(url, payload)
                    break
            }
            data.value = response.data
            return response.data
        } catch (err) {
            error.value = {
                message: err.response?.data?.message || err.message || 'An error occurred',
                errors: err.response?.data?.errors,
                status: err.response?.status || 0
            }
            return null
        } finally {
            loading.value = false
        }
    }

    // Execute immediately if option is set
    if (options.immediate) {
        execute()
    }

    return {
        data,
        error,
        loading,
        execute
    }
}

/**
 * Simple async request helper
 * Returns data directly or throws error
 */
export async function useRequest(method, url, payload = null, config = {}) {
    switch (method) {
        case 'get':
            return (await api.get(url, config)).data
        case 'post':
            return (await api.post(url, payload, config)).data
        case 'put':
            return (await api.put(url, payload, config)).data
        case 'patch':
            return (await api.patch(url, payload, config)).data
        case 'delete':
            return (await api.delete(url, config)).data
        default:
            throw new Error(`Unsupported method: ${method}`)
    }
}

