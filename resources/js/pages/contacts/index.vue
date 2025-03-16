<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted, computed, nextTick } from 'vue';
import axios from 'axios';

// Interfaces
interface Contact {
    id: number;
    name: string;
    phone_number: string;
    tags?: Tag[];
    status?: string;
}

interface Tag {
    id: number;
    name: string;
}

interface ContactForm {
    name: string;
    phone_number: string;
    tags: number[];
}

// Constantes
const API_ENDPOINTS = {
    contacts: '/api/contacts',
    tags: '/api/tags'
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Gestión de Contactos', href: '/contacts' }
];

// Estado reactivo
const contacts = ref<Contact[]>([]);
const availableTags = ref<Tag[]>([]);
const isLoading = ref(false);
const error = ref<string | null>(null);
const searchQuery = ref('');

// Estado del modal
const showCreateModal = ref(false);
const newContact = reactive<ContactForm>({ name: '', phone_number: '', tags: [] });

// Nuevo estado para autocompletado de etiquetas
const tagInput = ref('');
const filteredTags = ref<Tag[]>([]);
const tagSuggestionVisible = ref(false);
const selectedTags = ref<Tag[]>([]);

// Estado de edición
const editState = reactive({
    active: false,
    contact: null as Contact | null,
    prop: '',
});

// Hooks de ciclo de vida
onMounted(() => {
    fetchContacts();
    fetchTags();
});

// Obtención de datos
const fetchContacts = async () => {
    try {
        isLoading.value = true;
        error.value = null;
        const response = await axios.get(API_ENDPOINTS.contacts);
        contacts.value = response.data;
    } catch (err) {
        error.value = 'Error obteniendo contactos';
        console.error('Error obteniendo contactos:', err);
    } finally {
        isLoading.value = false;
    }
};

const fetchTags = async () => {
    try {
        const response = await axios.get(API_ENDPOINTS.tags);
        availableTags.value = response.data;
    } catch (err) {
        console.error('Error obteniendo etiquetas:', err);
    }
};

// Filtrado de contactos
const filteredContacts = computed(() => {
    if (!searchQuery.value) return contacts.value;
    const query = searchQuery.value.toLowerCase();
    return contacts.value.filter(contact =>
        contact.name.toLowerCase().includes(query) ||
        contact.phone_number.toLowerCase().includes(query) ||
        (contact.tags && contact.tags.some(tag => tag.name.toLowerCase().includes(query)))
    );
});

// Funciones para autocompletado de etiquetas
const filterTagSuggestions = () => {
    if (!tagInput.value) {
        filteredTags.value = [];
        tagSuggestionVisible.value = false;
        return;
    }
    
    const query = tagInput.value.toLowerCase();
    const alreadySelectedIds = selectedTags.value.map(tag => tag.id);
    
    filteredTags.value = availableTags.value.filter(tag => 
        tag.name.toLowerCase().includes(query) && 
        !alreadySelectedIds.includes(tag.id)
    );
    
    tagSuggestionVisible.value = filteredTags.value.length > 0;
};

const selectTag = (tag: Tag) => {
    // Añadir a la lista de etiquetas seleccionadas
    selectedTags.value.push(tag);
    
    // Actualizar los IDs en el formulario
    newContact.tags = selectedTags.value.map(t => t.id);
    
    // Limpiar input
    tagInput.value = '';
    tagSuggestionVisible.value = false;
};

const removeTag = (tagId: number) => {
    selectedTags.value = selectedTags.value.filter(tag => tag.id !== tagId);
    newContact.tags = selectedTags.value.map(tag => tag.id);
};

// Acciones del modal
const openCreateModal = () => {
    newContact.name = '';
    newContact.phone_number = '';
    newContact.tags = [];
    selectedTags.value = [];
    tagInput.value = '';
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
};

// Operaciones CRUD
const createContact = async () => {
    try {
        isLoading.value = true;
        
        const contactData = {
            name: newContact.name,
            phone_number: newContact.phone_number,
            tags: newContact.tags
        };
        
        await axios.post(API_ENDPOINTS.contacts, contactData);
        
        await fetchContacts();
        closeCreateModal();
    } catch (err) {
        error.value = 'Error creando contacto';
        console.error('Error creando contacto:', err);
    } finally {
        isLoading.value = false;
    }
};

const startEditing = (contact: Contact, prop: string) => {
    editState.contact = { ...contact };
    editState.prop = prop;
    editState.active = true;
};

const finishEditing = async () => {
    if (!editState.contact) return;
    
    try {
        isLoading.value = true;
        const contactToUpdate = { ...editState.contact };
        
        await axios.put(`${API_ENDPOINTS.contacts}/${contactToUpdate.id}`, {
            name: contactToUpdate.name,
            phone_number: contactToUpdate.phone_number
        });
        await fetchContacts();
        cancelEditing();
    } catch (err) {
        error.value = 'Error actualizando contacto';
        console.error('Error actualizando contacto:', err);
    } finally {
        isLoading.value = false;
    }
};

const cancelEditing = () => {
    editState.active = false;
    editState.contact = null;
    editState.prop = '';
};

const deleteContact = async (id: number) => {
    if (!confirm('¿Seguro que deseas eliminar este contacto?')) return;
    
    try {
        isLoading.value = true;
        await axios.delete(`${API_ENDPOINTS.contacts}/${id}`);
        await fetchContacts();
    } catch (err) {
        error.value = 'Error eliminando contacto';
        console.error('Error eliminando contacto:', err);
    } finally {
        isLoading.value = false;
    }
};

// Formato para mostrar etiquetas
const formatTags = (tags: Tag[] | undefined) => {
    if (!tags || tags.length === 0) return '-';
    return tags.map(tag => tag.name).join(', ');
};
</script>

<template>
    <Head title="Gestión de Contactos" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Contenido Principal -->
        <div class="bg-white shadow-md rounded-xl p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Gestión de Contactos</h2>
                <button @click="openCreateModal" class="btn-primary">
                    <span class="mr-1">+</span> Nuevo Contacto
                </button>
            </div>
            
            <!-- Búsqueda -->
            <div class="mb-4">
                <input
                    type="text"
                    v-model="searchQuery"
                    placeholder="Buscar contactos..."
                    class="shadow appearance-none border rounded w-64 py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                />
            </div>

            <!-- Estados de Carga y Error -->
            <div v-if="isLoading" class="flex justify-center py-4">
                <div class="loader"></div>
            </div>
            
            <div v-if="error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                <p>{{ error }}</p>
            </div>

            <!-- Tabla de Contactos -->
            <div class="overflow-x-auto bg-white rounded-lg shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teléfono</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Etiquetas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="contact in filteredContacts" :key="contact.id" class="hover:bg-gray-50">
                            <td 
                                @click="startEditing(contact, 'name')" 
                                class="px-6 py-4 whitespace-nowrap cursor-pointer hover:bg-blue-50"
                            >
                                <div v-if="editState.active && editState.contact?.id === contact.id && editState.prop === 'name'">
                                    <input 
                                        v-model="editState.contact.name" 
                                        @blur="finishEditing"
                                        @keyup.enter="finishEditing"
                                        @keyup.escape="cancelEditing"
                                        class="border rounded px-2 py-1 w-full"
                                        ref="editInput"
                                        autofocus
                                    />
                                </div>
                                <div v-else class="text-gray-900">
                                    {{ contact.name }}
                                </div>
                            </td>
                            <td 
                                @click="startEditing(contact, 'phone_number')" 
                                class="px-6 py-4 whitespace-nowrap cursor-pointer hover:bg-blue-50"
                            >
                                <div v-if="editState.active && editState.contact?.id === contact.id && editState.prop === 'phone_number'">
                                    <input 
                                        v-model="editState.contact.phone_number" 
                                        @blur="finishEditing"
                                        @keyup.enter="finishEditing"
                                        @keyup.escape="cancelEditing"
                                        class="border rounded px-2 py-1 w-full"
                                        ref="editInput"
                                        autofocus
                                    />
                                </div>
                                <div v-else class="text-gray-900">{{ contact.phone_number }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ formatTags(contact.tags) }}
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button @click="deleteContact(contact.id)" class="btn-danger">
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filteredContacts.length === 0 && !isLoading">
                            <td colspan="5" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                                No hay contactos disponibles
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal para Crear Contacto con autocompletado -->
        <div v-if="showCreateModal" class="fixed inset-0 overflow-y-auto z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
            
            <div class="relative bg-white rounded-lg max-w-md w-full mx-4 shadow-xl z-10">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Nuevo Contacto</h3>
                </div>
                
                <div class="p-6">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                            Nombre
                        </label>
                        <input 
                            id="name"
                            v-model="newContact.name" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                            type="text" 
                            placeholder="Nombre del contacto"
                        />
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">
                            Teléfono
                        </label>
                        <input 
                            id="phone"
                            v-model="newContact.phone_number" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                            type="text" 
                            placeholder="Número de teléfono"
                        />
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="tags">
                            Etiquetas
                        </label>

                        <!-- Etiquetas seleccionadas -->
                        <div class="flex flex-wrap gap-2 mb-2">
                            <div 
                                v-for="tag in selectedTags" 
                                :key="tag.id" 
                                class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-1 rounded flex items-center"
                            >
                                {{ tag.name }}
                                <button 
                                    @click="removeTag(tag.id)" 
                                    class="ml-1.5 text-blue-600 hover:text-blue-800"
                                    type="button"
                                    >
                                    <span class="text-xs font-bold">×</span>
                                </button>
                            </div>
                        </div>

                        <!-- Campo de autocompletado -->
                        <div class="relative">
                            <input 
                                type="text" 
                                v-model="tagInput" 
                                @input="filterTagSuggestions" 
                                @focus="filterTagSuggestions"
                                @click.stop
                                placeholder="Escribe para buscar etiquetas..." 
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            />
                            
                            <!-- Lista de sugerencias -->
                            <div v-if="tagSuggestionVisible" class="absolute z-10 mt-1 w-full bg-white shadow-lg rounded-md border border-gray-200 max-h-60 overflow-auto">
                                <div 
                                    v-for="tag in filteredTags" 
                                    :key="tag.id" 
                                    @click="selectTag(tag)"
                                    class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm"
                                >
                                    {{ tag.name }}
                                </div>
                                <div v-if="filteredTags.length === 0" class="px-4 py-2 text-gray-500 text-sm">
                                    No se encontraron etiquetas
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-2">
                        <button @click="closeCreateModal" class="btn-secondary">
                            Cancelar
                        </button>
                        <button @click="createContact" class="btn-primary" :disabled="isLoading">
                            Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Botones */
.btn-primary {
    @apply bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-md shadow transition duration-200 ease-in-out;
}

.btn-secondary {
    @apply bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-md shadow transition duration-200 ease-in-out;
}

.btn-danger {
    @apply bg-red-500 hover:bg-red-600 text-white font-medium py-1.5 px-3 rounded-md shadow transition duration-200 ease-in-out;
}

/* Tabla */
table th {
    @apply font-medium text-left;
}

/* Indicador de carga */
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