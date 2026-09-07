/**
 * Auth Store (Pinia)
 * Manages user authentication state and fetches user data from API
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { apiGet } from '@/services/api'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {
    // State
    const user = ref(null)
    const loading = ref(false)
    const error = ref(null)
    const company = ref(null)
    const isDC = ref(false)
    const isPA = ref(false)
    const isPG = ref(false)
    // Getters
    const isAuthenticated = computed(() => user.value !== null)
    const userRole = computed(() => user.value?.role)

    // Actions
    /**
     * Fetch current user from /api/me endpoint
     * This should be called on app initialization and after login
     */
    async function fetchUser() {
        loading.value = true
        error.value = null

        try {
            const response = await apiGet('/me')
            
            if (response.data.success && response.data.user) {
                user.value = response.data.user
                localStorage.setItem('user', response.data.user?.id)
                isDC.value = response.data.user?.isDC
                isPA.value = response.data.user?.isPA
                isPG.value = response.data.user?.isPG
            } else {
                user.value = null
            }
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Failed to fetch user'
            user.value = null
            
            // If 401 or 403, user is not authenticated
            if (err.response?.status === 401 || err.response?.status === 403) {
                // Clear any stored auth data
                localStorage.removeItem('auth_token')
            }
        } finally {
            loading.value = false
        }
    }
    async function fetchCurrentCompany() {
        try {
            const response = await axios.get('/get-current-company')
            if (response.data.success && response.data.company) {
                company.value = response.data.company
                localStorage.setItem('company', response.data.company.id)
            }
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Failed to fetch current company'
            company.value = null
        } 
    }

    /**
     * Switch to a different company
     */
    async function switchCompany(companyId) {
        loading.value = true
        error.value = null

        try {
            const response = await axios.post('/api/switch-company', {
                company_id: companyId
            })

            if (response.data.success && response.data.company) {
                company.value = response.data.company
                return { success: true, message: response.data.message }
            } else {
                throw new Error('Failed to switch company')
            }
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Failed to switch company'
            return { success: false, message: error.value }
        } finally {
            loading.value = false
        }
    }

    /**
     * Fetch all companies for switching
     */
    async function fetchAllCompanies() {
        try {
            const response = await axios.get('/get-companies')
            if (response.data.success && response.data.collection) {
                return response.data.collection
            }
            return []
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Failed to fetch companies'
            return []
        }
    }

    /**
     * Clear user data (for logout)
     */
    function clearUser() {
        user.value = null
        error.value = null
        localStorage.removeItem('auth_token')
    }

    /**
     * Set user data (for manual updates if needed)
     */
    function setUser(userData) {
        user.value = userData
    }
    

    return {
        // State
        user,
        loading,
        error,
        company,
        isDC,
        isPA,
        isPG,
        // Getters
        isAuthenticated,
        userRole,
        // Actions
        fetchUser,
        fetchCurrentCompany,
        switchCompany,
        fetchAllCompanies,
        clearUser,
        setUser
    }
})

