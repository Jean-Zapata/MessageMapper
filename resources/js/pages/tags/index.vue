<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';

interface Tag {
    id: number;
    name: string;
}

// Breadcrumbs para la navegación
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Gestión de Etiquetas',
        href: '/tags',
    }
];

// Referencias y estado
const tags = ref<Tag[]>([]);
const newTag = ref<{ name: string }>({ name: '' });

// Control para edición inline
const editActive = ref(false);
const editingTag = ref<Tag | null>(null);
const editProp = ref<string>('');
const showCreateModal = ref(false);

// Funciones
onMounted(() => {
    fetchTags();
});

const fetchTags = async () => {
    try {
        const response = await axios.get('/api/tags');
        tags.value = response.data;
    } catch (error) {
        console.error('Error obteniendo etiquetas:', error);
    }
};

const openCreateModal = () => {
    newTag.value = { name: '' };
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
};

const createTag = async () => {
    try {
        await axios.post('/api/tags', newTag.value);
        await fetchTags();
        closeCreateModal();
    } catch (error) {
        console.error('Error creando etiqueta:', error);
    }
};

const startEditing = (tag: Tag, prop: string) => {
    editingTag.value = { ...tag };
    editProp.value = prop;
    editActive.value = true;
};

const finishEditing = async () => {
    if (editingTag.value) {
        try {
            await axios.put(`/api/tags/${editingTag.value.id}`, editingTag.value);
            await fetchTags();
            editActive.value = false;
        } catch (error) {
            console.error('Error actualizando etiqueta:', error);
        }
    }
};

const cancelEditing = () => {
    editActive.value = false;
    editingTag.value = null;
};

const deleteTag = async (id: number) => {
    if (confirm('¿Seguro que deseas eliminar esta etiqueta?')) {
        try {
            await axios.delete(`/api/tags/${id}`);
            await fetchTags();
        } catch (error) {
            console.error('Error eliminando etiqueta:', error);
        }
    }
};
</script>

<template>
    <Head title="Gestión de Etiquetas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Gestión de Etiquetas</h2>
                <button @click="openCreateModal" 
                        class="flex items-center gap-2 px-3 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Nueva Etiqueta
                </button>
            </div>

            <div class="relative flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border overflow-hidden">
                <div class="p-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                        Nombre
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
                                <tr v-for="tag in tags" :key="tag.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td 
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" 
                                        @click="startEditing(tag, 'name')"
                                    >
                                        {{ tag.name }}
                                        <span class="ml-2 text-gray-400 text-xs"></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                        <button @click="deleteTag(tag.id)" 
                                                class="inline-flex items-center px-3 py-1.5 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            Eliminar
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="tags.length === 0">
                                    <td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                        No hay etiquetas disponibles
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal de Crear (usando Teleport para renderizar fuera del componente) -->
            <Teleport to="body">
                <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4">
                        <div class="bg-primary text-white p-4 border-b border-gray-200 dark:border-gray-700 rounded-t-lg flex justify-between items-center">
                            <h5 class="text-lg font-medium">Nueva Etiqueta</h5>
                            <button type="button" class="text-white hover:text-gray-200" @click="closeCreateModal">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                        <div class="p-4">
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre</label>
                                <input type="text" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white" 
                                       v-model="newTag.name">
                            </div>
                        </div>
                        <div class="flex justify-end p-4 border-t border-gray-200 dark:border-gray-700">
                            <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 dark:bg-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 mr-2" 
                                    @click="closeCreateModal">Cancelar</button>
                            <button class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary/90" 
                                    @click="createTag">Guardar</button>
                        </div>
                    </div>
                </div>
            </Teleport>

            <!-- Modal de Edición (similar al ejemplo de Vuesax) -->
            <Teleport to="body">
                <div v-if="editActive && editingTag" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4">
                        <div class="bg-blue-500 text-white p-4 border-b border-gray-200 dark:border-gray-700 rounded-t-lg flex justify-between items-center">
                            <h5 class="text-lg font-medium">Editar {{ editProp === 'name' ? 'Nombre' : '' }}</h5>
                            <button type="button" class="text-white hover:text-gray-200" @click="cancelEditing">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                        <div class="p-4">
                            <div class="mb-3">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ editProp === 'name' ? 'Nombre' : '' }}
                                </label>
                                <input 
                                    type="text" 
                                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white" 
                                    v-model="editingTag[editProp]"
                                    @keyup.enter="finishEditing"
                                >
                            </div>
                        </div>
                        <div class="flex justify-end p-4 border-t border-gray-200 dark:border-gray-700">
                            <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 dark:bg-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 mr-2" 
                                    @click="cancelEditing">Cancelar</button>
                            <button class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-md hover:bg-blue-600" 
                                    @click="finishEditing">Actualizar</button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Estilos para efecto de celdas editables */
td[class*="cursor-pointer"]:hover {
    position: relative;
}

td[class*="cursor-pointer"]:hover::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 24px;
    right: 24px;
    height: 2px;
    background-color: #3b82f6;
}
</style>