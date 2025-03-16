<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';

// Interfaces
interface Device {
    id: number;
    name: string;
    phone_number: string;
    status?: string;
    image_url?: string;
}

interface Contact {
    id: number;
    name: string;
    phone_number: string;
}

interface Tag {
    id: number;
    name: string;
}

interface Campaign {
    id: number;
    name: string;
    message: string;
    device_id: number;
    status: string;
    scheduled_time: string;
    created_at?: string;
    updated_at?: string;
    device?: Device;
    contacts?: Contact[];
}

interface CampaignForm {
    name: string;
    message: string;
    device_id: number | null;
    tags: number[];
    scheduled_time: string;
}

// Constants
const API_ENDPOINTS = {
    campaigns: '/api/campaigns',
    devices: '/api/devices',
    tags: '/api/tags',
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Gestión de Campañas', href: '/campaigns' }
];

// Status options
const statusLabels = {
    'scheduled': 'Programada',
    'in_progress': 'En Progreso',
    'completed': 'Completada',
    'failed': 'Fallida'
};

// Reactive state
const campaigns = ref<Campaign[]>([]);
const devices = ref<Device[]>([]);
const tags = ref<Tag[]>([]);
const isLoading = ref(false);
const error = ref<string | null>(null);

// Modal state
const showCreateModal = ref(false);
const newCampaign = reactive<CampaignForm>({
    name: '',
    message: '',
    device_id: null,
    tags: [],
    scheduled_time: new Date().toISOString().substring(0, 16) // Formato: 'YYYY-MM-DDThh:mm'
});

// Edit state
const editState = reactive({
    active: false,
    campaign: null as Campaign | null,
});

// Computed properties
const activeDevices = computed(() => {
    return devices.value.filter(device => device.status === 'active');
});

// Lifecycle hooks
onMounted(() => {
    fetchCampaigns();
    fetchDevices();
    fetchTags();
    // Verificar campañas programadas cada minuto
    setInterval(checkScheduledCampaigns, 60000);
});

// Data fetching
const fetchCampaigns = async () => {
    try {
        isLoading.value = true;
        error.value = null;
        const response = await axios.get(API_ENDPOINTS.campaigns);
        campaigns.value = response.data;
    } catch (err) {
        error.value = 'Error obteniendo campañas';
        console.error('Error obteniendo campañas:', err);
    } finally {
        isLoading.value = false;
    }
};

const fetchDevices = async () => {
    try {
        const response = await axios.get(API_ENDPOINTS.devices);
        devices.value = response.data;
    } catch (err) {
        console.error('Error obteniendo dispositivos:', err);
    }
};

const fetchTags = async () => {
    try {
        const response = await axios.get(API_ENDPOINTS.tags);
        tags.value = response.data;
    } catch (err) {
        console.error('Error obteniendo etiquetas:', err);
    }
};

// Check for scheduled campaigns
const checkScheduledCampaigns = () => {
    const now = new Date();
    campaigns.value.forEach(campaign => {
        if (campaign.status === 'scheduled') {
            const scheduledTime = new Date(campaign.scheduled_time);
            if (scheduledTime <= now) {
                sendCampaign(campaign.id);
            }
        }
    });
};

// Modal actions
const openCreateModal = () => {
    newCampaign.name = '';
    newCampaign.message = '';
    newCampaign.device_id = null;
    newCampaign.tags = [];
    newCampaign.scheduled_time = new Date().toISOString().substring(0, 16);
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
};

// CRUD operations
const createCampaign = async () => {
    try {
        isLoading.value = true;
        
        await axios.post(API_ENDPOINTS.campaigns, newCampaign);
        
        await fetchCampaigns();
        closeCreateModal();
    } catch (err) {
        error.value = 'Error creando campaña';
        console.error('Error creando campaña:', err);
    } finally {
        isLoading.value = false;
    }
};

const openEditModal = (campaign: Campaign) => {
    editState.campaign = { 
        ...campaign,
        scheduled_time: new Date(campaign.scheduled_time).toISOString().substring(0, 16)
    };
    editState.active = true;
};

const closeEditModal = () => {
    editState.active = false;
    editState.campaign = null;
};

const updateCampaign = async () => {
    if (!editState.campaign) return;
    
    try {
        isLoading.value = true;
        
        const campaignData = {
            name: editState.campaign.name,
            message: editState.campaign.message,
            device_id: editState.campaign.device_id,
            tags: editState.campaign.tags || [],
            scheduled_time: editState.campaign.scheduled_time
        };
        
        await axios.put(`${API_ENDPOINTS.campaigns}/${editState.campaign.id}`, campaignData);
        await fetchCampaigns();
        closeEditModal();
    } catch (err) {
        error.value = 'Error actualizando campaña';
        console.error('Error actualizando campaña:', err);
    } finally {
        isLoading.value = false;
    }
};

const deleteCampaign = async (id: number) => {
    if (!confirm('¿Seguro que deseas eliminar esta campaña?')) return;
    
    try {
        isLoading.value = true;
        await axios.delete(`${API_ENDPOINTS.campaigns}/${id}`);
        await fetchCampaigns();
    } catch (err) {
        error.value = 'Error eliminando campaña';
        console.error('Error eliminando campaña:', err);
    } finally {
        isLoading.value = false;
    }
};

const sendCampaign = async (id: number) => {
    try {
        isLoading.value = true;
        await axios.post(`${API_ENDPOINTS.campaigns}/${id}/send`);
        await fetchCampaigns();
    } catch (err) {
        error.value = 'Error enviando mensajes de campaña';
        console.error('Error enviando mensajes de campaña:', err);
    } finally {
        isLoading.value = false;
    }
};

// Helpers
const formatDateTime = (dateString: string): string => {
    const date = new Date(dateString);
    return date.toLocaleString('es-PE', { 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getDeviceName = (deviceId: number): string => {
    const device = devices.value.find(d => d.id === deviceId);
    return device ? device.name : 'Dispositivo no encontrado';
};

const getTagNames = (tagIds: number[]): string => {
    return tagIds.map(id => {
        const tag = tags.value.find(t => t.id === id);
        return tag ? tag.name : '';
    }).filter(Boolean).join(', ');
};

const isScheduledTimeValid = (scheduledTime: string): boolean => {
    const scheduledDate = new Date(scheduledTime);
    const now = new Date();
    return scheduledDate > now;
};
</script>

<template>
    <Head title="Gestión de Campañas" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Main Content -->
        <div class="bg-white shadow-md rounded-xl p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Gestión de Campañas</h2>
                <button @click="openCreateModal" class="btn-primary">
                    <span class="mr-1">+</span> Nueva Campaña
                </button>
            </div>

            <!-- Loading and Error States -->
            <div v-if="isLoading" class="flex justify-center py-4">
                <div class="loader"></div>
            </div>
            
            <div v-if="error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                <p>{{ error }}</p>
            </div>

            <!-- Campaigns Table -->
            <div class="overflow-x-auto bg-white rounded-lg shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dispositivo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Programada para</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="campaign in campaigns" :key="campaign.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-gray-900 font-medium">{{ campaign.name }}</div>
                                <div class="text-gray-500 text-sm truncate max-w-xs">{{ campaign.message }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div v-if="campaign.device" class="text-gray-900">
                                    {{ campaign.device.name }}
                                </div>
                                <div v-else class="text-gray-500">
                                    Dispositivo no encontrado
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-gray-900">{{ formatDateTime(campaign.scheduled_time) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span 
                                    :class="[
                                        'px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full',
                                        campaign.status === 'scheduled' ? 'bg-yellow-100 text-yellow-800' : 
                                        campaign.status === 'in_progress' ? 'bg-blue-100 text-blue-800' :
                                        campaign.status === 'completed' ? 'bg-green-100 text-green-800' :
                                        'bg-red-100 text-red-800'
                                    ]"
                                >
                                    {{ statusLabels[campaign.status] || campaign.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button 
                                        v-if="campaign.status === 'scheduled'"
                                        @click="openEditModal(campaign)" 
                                        class="btn-secondary"
                                        title="Editar campaña"
                                    >
                                        Editar
                                    </button>
                                    <button 
                                        v-if="campaign.status === 'scheduled'"
                                        @click="sendCampaign(campaign.id)" 
                                        class="btn-primary"
                                        title="Enviar ahora"
                                    >
                                        Enviar Ahora
                                    </button>
                                    <button 
                                        v-if="campaign.status === 'scheduled'"
                                        @click="deleteCampaign(campaign.id)" 
                                        class="btn-danger"
                                        title="Eliminar campaña"
                                    >
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="campaigns.length === 0 && !isLoading">
                            <td colspan="5" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                                No hay campañas disponibles
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create Campaign Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 overflow-y-auto z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
            
            <div class="relative bg-white rounded-lg max-w-lg w-full mx-4 shadow-xl z-10">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Nueva Campaña</h3>
                </div>
                
                <div class="p-6">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                            Nombre
                        </label>
                        <input 
                            id="name"
                            v-model="newCampaign.name" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                            type="text" 
                            placeholder="Nombre de la campaña"
                        />
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="device">
                            Dispositivo
                        </label>
                        <select 
                            id="device"
                            v-model="newCampaign.device_id" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        >
                            <option :value="null" disabled selected>Seleccione un dispositivo</option>
                            <option v-for="device in activeDevices" :key="device.id" :value="device.id">
                                {{ device.name }} ({{ device.phone_number }})
                            </option>
                        </select>
                        <p v-if="activeDevices.length === 0" class="text-xs text-red-500 mt-1">
                            No hay dispositivos activos disponibles
                        </p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="tags">
                            Etiquetas (Grupos de Contactos)
                        </label>
                        <select 
                            id="tags"
                            v-model="newCampaign.tags" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            multiple
                        >
                            <option v-for="tag in tags" :key="tag.id" :value="tag.id">
                                {{ tag.name }}
                            </option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Mantenga presionado Ctrl/Cmd para seleccionar múltiples etiquetas</p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="scheduled-time">
                            Fecha y Hora Programada
                        </label>
                        <input 
                            id="scheduled-time"
                            v-model="newCampaign.scheduled_time" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                            type="datetime-local" 
                        />
                        <p v-if="!isScheduledTimeValid(newCampaign.scheduled_time)" class="text-xs text-red-500 mt-1">
                            La fecha programada debe ser en el futuro
                        </p>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="message">
                            Mensaje
                        </label>
                        <textarea 
                            id="message"
                            v-model="newCampaign.message" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                            rows="4"
                            placeholder="Escriba el mensaje para la campaña"
                        ></textarea>
                    </div>
                    
                    <div class="flex justify-end gap-2">
                        <button @click="closeCreateModal" class="btn-secondary">
                            Cancelar
                        </button>
                        <button 
                            @click="createCampaign" 
                            class="btn-primary" 
                            :disabled="isLoading || !newCampaign.device_id || !newCampaign.name || !newCampaign.message || newCampaign.tags.length === 0 || !isScheduledTimeValid(newCampaign.scheduled_time)"
                        >
                            Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Campaign Modal -->
        <div v-if="editState.active && editState.campaign" class="fixed inset-0 overflow-y-auto z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
            
            <div class="relative bg-white rounded-lg max-w-lg w-full mx-4 shadow-xl z-10">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Editar Campaña</h3>
                </div>
                
                <div class="p-6">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-name">
                            Nombre
                        </label>
                        <input 
                            id="edit-name"
                            v-model="editState.campaign.name" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                            type="text" 
                        />
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-device">
                            Dispositivo
                        </label>
                        <select 
                            id="edit-device"
                            v-model="editState.campaign.device_id" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        >
                            <option :value="null" disabled>Seleccione un dispositivo</option>
                            <option v-for="device in activeDevices" :key="device.id" :value="device.id">
                                {{ device.name }} ({{ device.phone_number }})
                            </option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-tags">
                            Etiquetas (Grupos de Contactos)
                        </label>
                        <select 
                            id="edit-tags"
                            v-model="editState.campaign.tags" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            multiple
                        >
                            <option v-for="tag in tags" :key="tag.id" :value="tag.id">
                                {{ tag.name }}
                            </option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-scheduled-time">
                            Fecha y Hora Programada
                        </label>
                        <input 
                            id="edit-scheduled-time"
                            v-model="editState.campaign.scheduled_time" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                            type="datetime-local" 
                        />
                        <p v-if="!isScheduledTimeValid(editState.campaign.scheduled_time)" class="text-xs text-red-500 mt-1">
                            La fecha programada debe ser en el futuro
                        </p>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="edit-message">
                            Mensaje
                        </label>
                        <textarea 
                            id="edit-message"
                            v-model="editState.campaign.message" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                            rows="4"
                        ></textarea>
                    </div>
                    
                    <div class="flex justify-end gap-2">
                        <button @click="closeEditModal" class="btn-secondary">
                            Cancelar
                        </button>
                        <button 
                            @click="updateCampaign" 
                            class="btn-primary" 
                            :disabled="isLoading || !editState.campaign.device_id || !editState.campaign.name || !editState.campaign.message || !isScheduledTimeValid(editState.campaign.scheduled_time)"
                        >
                            Actualizar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Buttons */
.btn-primary {
    @apply bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-md shadow transition duration-200 ease-in-out;
}

.btn-secondary {
    @apply bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-md shadow transition duration-200 ease-in-out;
}

.btn-danger {
    @apply bg-red-500 hover:bg-red-600 text-white font-medium py-1.5 px-3 rounded-md shadow transition duration-200 ease-in-out;
}

/* Table */
table th {
    @apply font-medium text-left;
}

/* Loading spinner */
.loader {
    @apply w-8 h-8 border-4 border-blue-200 rounded-full;
    border-top-color: #3b82f6;
    animation: spin 1s infinite linear;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>