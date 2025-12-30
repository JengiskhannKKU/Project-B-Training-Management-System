import { ref } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

export function useAdminRequests() {
    const toast = useToast();
    const rawRequests = ref([]);
    const isLoading = ref(false);
    const showApiLogin = ref(false);

    const ensureCsrf = () => axios.get('/sanctum/csrf-cookie');

    const fetchRequests = async () => {
        isLoading.value = true;
        try {
            await ensureCsrf();
            const { data } = await axios.get('/api/admin/requests');
            const list = data?.data || data || [];
            rawRequests.value = list;
        } catch (error) {
            if ([401, 403, 419].includes(error?.response?.status)) {
                showApiLogin.value = true;
            }
            toast.error(
                error?.response?.data?.message ||
                    error?.message ||
                    'Unable to load requests.'
            );
        } finally {
            isLoading.value = false;
        }
    };

    const approveRequest = async (id, adminNote = null) => {
        try {
            await ensureCsrf();
            await axios.post(`/api/admin/requests/${id}/approve`, {
                admin_note: adminNote,
            });
            toast.success('Request approved.');
            await fetchRequests();
        } catch (error) {
            if ([401, 403, 419].includes(error?.response?.status)) {
                showApiLogin.value = true;
            }
            toast.error(
                error?.response?.data?.message ||
                    error?.message ||
                    'Unable to approve request.'
            );
        }
    };

    const rejectRequest = async (id, adminNote = null) => {
        try {
            await ensureCsrf();
            await axios.post(`/api/admin/requests/${id}/reject`, {
                admin_note: adminNote,
            });
            toast.success('Request rejected.');
            await fetchRequests();
        } catch (error) {
            if ([401, 403, 419].includes(error?.response?.status)) {
                showApiLogin.value = true;
            }
            toast.error(
                error?.response?.data?.message ||
                    error?.message ||
                    'Unable to reject request.'
            );
        }
    };

    const setPendingRequest = async (id, adminNote = null) => {
        try {
            await ensureCsrf();
            await axios.post(`/api/admin/requests/${id}/pending`, {
                admin_note: adminNote,
            });
            toast.success('Request set to pending.');
            await fetchRequests();
        } catch (error) {
            if ([401, 403, 419].includes(error?.response?.status)) {
                showApiLogin.value = true;
            }
            toast.error(
                error?.response?.data?.message ||
                    error?.message ||
                    'Unable to set request to pending.'
            );
        }
    };

    const handleStatusChange = async (requestId, action, adminNote = null) => {
        switch (action) {
            case 'approve':
                await approveRequest(requestId, adminNote);
                break;
            case 'reject':
                await rejectRequest(requestId, adminNote);
                break;
            case 'pending':
                await setPendingRequest(requestId, adminNote);
                break;
        }
    };

    const setBearerToken = (token) => {
        localStorage.setItem('api_token', token);
        axios.defaults.headers.common.Authorization = `Bearer ${token}`;
    };

    const handleApiLogin = async (email, password) => {
        try {
            await ensureCsrf();
            const { data } = await axios.post('/api/auth/login', {
                email,
                password,
            });
            const token = data?.data?.token || data?.token;
            if (token) {
                setBearerToken(token);
                toast.success('API token saved. Retry your action.');
                showApiLogin.value = false;
                return { success: true };
            } else {
                return { success: false, error: 'No token returned. Check credentials.' };
            }
        } catch (error) {
            return {
                success: false,
                error: error?.response?.data?.message ||
                    error?.message ||
                    'Login failed. Check credentials.'
            };
        }
    };

    return {
        rawRequests,
        isLoading,
        showApiLogin,
        fetchRequests,
        approveRequest,
        rejectRequest,
        setPendingRequest,
        handleStatusChange,
        handleApiLogin,
    };
}
