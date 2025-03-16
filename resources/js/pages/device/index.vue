<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

// Interfaces
interface Device {
    id: number;
    name: string;
    phone_number: string; // Cambiado de 'phone' a 'phone_number' para coincidir con el backend
    status?: string;
    image_url?: string;
}

interface DeviceForm {
    name: string;
    phone_number: string; // Cambiado de 'phone' a 'phone_number'
    image?: File | null;
}

// Constants
const PHONE_PREFIX = '+51';
const API_ENDPOINTS = {
    devices: '/api/devices',
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Gestión de Dispositivos', href: '/devices' }
];

// Reactive state
const devices = ref<Device[]>([]);
const isLoading = ref(false);
const error = ref<string | null>(null);

// Modal state
const showCreateModal = ref(false);
const newDevice = reactive<DeviceForm>({ name: '', phone_number: '', image: null });

// Edit state
const editState = reactive({
    active: false,
    device: null as Device | null,
    prop: '',
});

// Lifecycle hooks
onMounted(() => {
    fetchDevices();
});

// Data fetching
const fetchDevices = async () => {
    try {
        isLoading.value = true;
        error.value = null;
        const response = await axios.get(API_ENDPOINTS.devices);
        devices.value = response.data;
    } catch (err) {
        error.value = 'Error obteniendo dispositivos';
        console.error('Error obteniendo dispositivos:', err);
    } finally {
        isLoading.value = false;
    }
};

// Helpers
const formatPhoneNumber = (phone: string): string => {
    return phone.startsWith(PHONE_PREFIX) ? phone : `${PHONE_PREFIX}${phone}`;
};

// Modal actions
const openCreateModal = () => {
    newDevice.name = '';
    newDevice.phone_number = '';
    newDevice.image = null;
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
};

// File handling
const handleFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
        newDevice.image = input.files[0];
    }
};

// CRUD operations
const createDevice = async () => {
    try {
        isLoading.value = true;
        
        // Usar FormData para poder enviar la imagen
        const formData = new FormData();
        formData.append('name', newDevice.name);
        formData.append('phone_number', formatPhoneNumber(newDevice.phone_number));
        
        if (newDevice.image) {
            formData.append('image', newDevice.image);
        }
        
        await axios.post(API_ENDPOINTS.devices, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        
        await fetchDevices();
        closeCreateModal();
    } catch (err) {
        error.value = 'Error creando dispositivo';
        console.error('Error creando dispositivo:', err);
    } finally {
        isLoading.value = false;
    }
};

const startEditing = (device: Device, prop: string) => {
    editState.device = { ...device };
    editState.prop = prop;
    editState.active = true;
};

const finishEditing = async () => {
    if (!editState.device) return;
    
    try {
        isLoading.value = true;
        const deviceToUpdate = { ...editState.device };
        
        if (editState.prop === 'phone_number') {
            deviceToUpdate.phone_number = formatPhoneNumber(deviceToUpdate.phone_number);
        }
        
        await axios.put(`${API_ENDPOINTS.devices}/${deviceToUpdate.id}`, deviceToUpdate);
        await fetchDevices();
        cancelEditing();
    } catch (err) {
        error.value = 'Error actualizando dispositivo';
        console.error('Error actualizando dispositivo:', err);
    } finally {
        isLoading.value = false;
    }
};

const cancelEditing = () => {
    editState.active = false;
    editState.device = null;
    editState.prop = '';
};

const deleteDevice = async (id: number) => {
    if (!confirm('¿Seguro que deseas eliminar este dispositivo?')) return;
    
    try {
        isLoading.value = true;
        await axios.delete(`${API_ENDPOINTS.devices}/${id}`);
        await fetchDevices();
    } catch (err) {
        error.value = 'Error eliminando dispositivo';
        console.error('Error eliminando dispositivo:', err);
    } finally {
        isLoading.value = false;
    }
};

const toggleDeviceStatus = async (id: number) => {
    try {
        isLoading.value = true;
        await axios.patch(`${API_ENDPOINTS.devices}/${id}/toggle-status`);
        await fetchDevices();
    } catch (err) {
        error.value = 'Error cambiando estado del dispositivo';
        console.error('Error cambiando estado del dispositivo:', err);
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <Head title="Gestión de Dispositivos" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Main Content -->
        <div class="bg-white shadow-md rounded-xl p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Gestión de Dispositivos</h2>
                <button @click="openCreateModal" class="btn-primary">
                    <span class="mr-1">+</span> Nuevo Dispositivo
                </button>
            </div>

            <!-- Loading and Error States -->
            <div v-if="isLoading" class="flex justify-center py-4">
                <div class="loader"></div>
            </div>
            
            <div v-if="error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                <p>{{ error }}</p>
            </div>

            <!-- Devices Table -->
            <div class="overflow-x-auto bg-white rounded-lg shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teléfono</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="device in devices" :key="device.id" class="hover:bg-gray-50">
                            <td 
                                @click="startEditing(device, 'name')" 
                                class="px-6 py-4 whitespace-nowrap cursor-pointer hover:bg-blue-50"
                            >
                                <div v-if="editState.active && editState.device?.id === device.id && editState.prop === 'name'">
                                    <input 
                                        v-model="editState.device.name" 
                                        @blur="finishEditing"
                                        @keyup.enter="finishEditing"
                                        @keyup.escape="cancelEditing"
                                        class="border rounded px-2 py-1 w-full"
                                        ref="editInput"
                                        autofocus
                                    />
                                </div>
                                <div v-else class="text-gray-900">
                                    <div class="flex items-center">
                                        <img 
                                            v-if="device.image_url" 
                                            :src="device.image_url" 
                                            class="h-8 w-8 rounded-full mr-2 object-cover"
                                            alt="Device"
                                        />
                                        {{ device.name }}
                                    </div>
                                </div>
                            </td>
                            <td 
                                @click="startEditing(device, 'phone_number')" 
                                class="px-6 py-4 whitespace-nowrap cursor-pointer hover:bg-blue-50"
                            >
                                <div v-if="editState.active && editState.device?.id === device.id && editState.prop === 'phone_number'">
                                    <input 
                                        v-model="editState.device.phone_number" 
                                        @blur="finishEditing"
                                        @keyup.enter="finishEditing"
                                        @keyup.escape="cancelEditing"
                                        class="border rounded px-2 py-1 w-full"
                                        ref="editInput"
                                        autofocus
                                    />
                                </div>
                                <div v-else class="text-gray-900">{{ device.phone_number }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span 
                                    :class="[
                                        'px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full',
                                        device.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                                    ]"
                                >
                                    {{ device.status === 'active' ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button 
                                        @click="toggleDeviceStatus(device.id)" 
                                        class="btn-secondary"
                                        :title="device.status === 'active' ? 'Desactivar' : 'Activar'"
                                    >
                                        {{ device.status === 'active' ? 'Desactivar' : 'Activar' }}
                                    </button>
                                    <button @click="deleteDevice(device.id)" class="btn-danger">
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="devices.length === 0 && !isLoading">
                            <td colspan="4" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                                No hay dispositivos disponibles
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create Device Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 overflow-y-auto z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
            
            <div class="relative bg-white rounded-lg max-w-md w-full mx-4 shadow-xl z-10">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Nuevo Dispositivo</h3>
                </div>
                
                <div class="p-6">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                            Nombre
                        </label>
                        <input 
                            id="name"
                            v-model="newDevice.name" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                            type="text" 
                            placeholder="Nombre del dispositivo"
                        />
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">
                            Teléfono
                        </label>
                        <input 
                            id="phone"
                            v-model="newDevice.phone_number" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                            type="text" 
                            placeholder="Número de teléfono"
                        />
                        <p class="text-xs text-gray-500 mt-1">Se añadirá automáticamente el prefijo +51 si no lo incluyes</p>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="image">
                            Imagen (opcional)
                        </label>
                        <input 
                            id="image"
                            type="file" 
                            accept="image/*"
                            @change="handleFileChange"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        />
                    </div>
                    
                    <div class="flex justify-end gap-2">
                        <button @click="closeCreateModal" class="btn-secondary">
                            Cancelar
                        </button>
                        <button @click="createDevice" class="btn-primary" :disabled="isLoading">
                            Guardar
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