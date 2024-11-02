<?php

namespace app\modules\ui\generator\framework\vue;

use app\modules\ui\generator\framework\FrameworkStrategy;
use yii\gii\CodeFile;
use Yii;
use app\modules\ui\generator\framework\fixtures\DatabaseFixtures;

class VueStrategy implements FrameworkStrategy
{
  public function runCommand($viewName, $projectPath)
  {
    // First create Vue project if it doesn't exist
    if (!is_dir($projectPath . $viewName)) {
      $npmCommand = 'vue create ' . escapeshellarg($viewName) . ' --default';
      $this->executeCommand($npmCommand, $projectPath);
    }

    // Install dependencies
    $dependencies = [
      'axios',
      'pinia',
      '@faker-js/faker'
    ];

    $installCommand = 'npm install ' . implode(' ', $dependencies);
    $this->executeCommand($installCommand, $projectPath);

    // Generate and save all files
    $this->generateAndSaveFiles($viewName, $projectPath);
  }

  private function generateAndSaveFiles($model, $projectPath)
  {

    try {
      // Create directories
      $dirs = [
        $model . '/src/components/' . strtolower($model),
        $model . '/src/stores',
        $model . '/src/services',
        $model . '/src/api'  // Add this new directory
      ];

      foreach ($dirs as $dir) {
        $fullPath = $projectPath . $dir;
        if (!is_dir($fullPath)) {
          mkdir($fullPath, 0777, true);
        }
      }

      // Generate and save files with explicit paths and error checking
      $files = [
        // Axios setup
        $model . '/src/api/axios.js' => $this->generateAxiosInstance($model),
        $model . '/src/api/' . strtolower($model) . 'Api.js' => $this->generateApiService($model),

        // Store
        $model . '/src/stores/' . strtolower($model) . 'Store.js' => $this->generateStore($model),
        $model . '/src/stores/index.js' => $this->generatePiniaSetup(),

        // Components
        $model . '/src/components/' . strtolower($model) . '/' . $model . 'Main.vue' => $this->generateMainComponent($model),
        $model . '/src/components/' . strtolower($model) . '/' . $model . 'List.vue' => $this->generateListComponent($model, []),
        $model . '/src/components/' . strtolower($model) . '/' . $model . 'Create.vue' => $this->generateCreateComponent($model, []),
        $model . '/src/components/' . strtolower($model) . '/' . $model . 'Update.vue' => $this->generateUpdateComponent($model, []),
        $model . '/src/components/' . strtolower($model) . '/' . $model . 'Delete.vue' => $this->generateDeleteComponent($model, [])
      ];

      // Save each file with error checking
      foreach ($files as $path => $content) {
        $fullPath = $projectPath . $path;

        // Ensure directory exists
        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
          mkdir($directory, 0777, true);
        }

        // Write file with error checking
        if (file_put_contents($fullPath, $content) === false) {
          throw new \Exception("Failed to write file: $fullPath");
        }

        Yii::info("Generated file: $fullPath", __METHOD__);
      }

      // Update main.js to include axios
      $mainJsPath = $projectPath . '/src/main.js';
      $mainJsContent = <<<JS
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { pinia } from './stores'
import axios from './api/axios'
import './assets/main.css'

const app = createApp(App)
app.use(pinia)
app.use(router)

// Make axios available globally
app.config.globalProperties.\$axios = axios

app.mount('#app')
JS;
      file_put_contents($mainJsPath, $mainJsContent);

      return true;

    } catch (\Exception $e) {
      Yii::error("Error generating files: " . $e->getMessage(), __METHOD__);
      throw $e;
    }
  }

  private function executeCommand($command, $projectPath)
  {
    $fullCommand = "cd " . escapeshellarg($projectPath) . " && $command";

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      $fullCommand = "cd /d " . escapeshellarg($projectPath) . " && $command";
    }

    $output = [];
    $returnVar = null;

    exec($fullCommand, $output, $returnVar);

    if ($returnVar !== 0) {
      Yii::error("Command failed: $command", __METHOD__);
      Yii::error("Output: " . implode("\n", $output), __METHOD__);
      throw new \Exception("Command failed: $command");
    }

    Yii::info("Command executed successfully: $command", __METHOD__);
    return $output;
  }

  public function generateListComponent($model, $schema)
  {
    $content = "<template>
  <div class=\"max-w-7xl mx-auto p-6\">
    <h2 class=\"text-2xl font-bold mb-6\">{$model} List</h2>
    <div class=\"overflow-x-auto shadow-md rounded-lg\">
      <table class=\"min-w-full divide-y divide-gray-200\">
        <thead class=\"bg-gray-50\">
          <tr>\n";
    foreach ($schema as $attribute => $type) {
      $content .= "          <th class=\"px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">{$attribute}</th>\n";
    }
    $content .= "          <th class=\"px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">Actions</th>
        </tr>
      </thead>
      <tbody class=\"bg-white divide-y divide-gray-200\">
        <tr v-for='item in items' :key='item.id' class=\"hover:bg-gray-50\">\n";
    foreach ($schema as $attribute => $type) {
      $content .= "          <td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-500\">{{ item.{$attribute} }}</td>\n";
    }
    $content .= "          <td class=\"px-6 py-4 whitespace-nowrap text-sm font-medium\">
            <button @click='editItem(item)' class=\"text-blue-600 hover:text-blue-900 mr-3\">Edit</button>
            <button @click='deleteItem(item)' class=\"text-red-600 hover:text-red-900\">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  </div>
</template>";

    // Keep existing script
    $content .= "\n<script>
export default {
  data() {
    return {
      items: []
    };
  },
  methods: {
    editItem(item) {
      this.\$emit('edit', item);
    },
    deleteItem(item) {
      this.\$emit('delete', item);
    }
  }
};
</script>";

    return $content;
  }

  public function generateCreateComponent($model, $schema)
  {
    $content = "<template>
  <div class=\"max-w-2xl mx-auto p-6\">
    <h2 class=\"text-2xl font-bold mb-6\">Create " . $model . "</h2>
    <form @submit.prevent='createItem' class=\"space-y-6\">\n";
    foreach ($schema as $attribute => $type) {
      $content .= "      <div class=\"space-y-1\">
        <label class=\"block text-sm font-medium text-gray-700\">{$attribute}</label>
        <input v-model='item.{$attribute}' type='" . $this->getInputType($type) . "' class=\"mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm\">
      </div>\n";
    }
    $content .= "      <button type='submit' class=\"w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2\">Create</button>
    </form>
  </div>
</template>";

    // Keep existing script
    $content .= "\n<script>
export default {
  data() {
    return {
      item: {}
    };
  },
  methods: {
    createItem() {
      this.\$emit('create', this.item);
      this.item = {};
    }
  }
};
</script>";

    return $content;
  }

  public function generateUpdateComponent($model, $schema)
  {
    $formFields = '';
    foreach ($schema as $attribute => $type) {
      $labelName = ucfirst($attribute);
      $formFields .= <<<HTML
      <div class="space-y-1">
        <label for="{$attribute}" class="block text-sm font-medium text-gray-700">{$labelName}</label>
        <input 
          id="{$attribute}"
          v-model="editedItem.{$attribute}" 
          type="{$this->getInputType($type)}" 
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
        >
      </div>

HTML;
    }
    $content = <<<VUE
<template>
  <div class="max-w-2xl mx-auto p-6">
    <h2 class="text-2xl font-bold mb-6">Update {$model}</h2>
    <form @submit.prevent="updateItem" class="space-y-6">
{$formFields}
      <div class="flex justify-end space-x-3">
        <button 
          type="button"
          @click="\$emit('cancel')"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          Cancel
        </button>
        <button 
          type="submit" 
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          Save Changes
        </button>
      </div>
    </form>
  </div>
</template>

<script>
export default {
  props: {
    item: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      editedItem: JSON.parse(JSON.stringify(this.item))
    };
  },
  watch: {
    item: {
      handler(newVal) {
        this.editedItem = JSON.parse(JSON.stringify(newVal));
      },
      deep: true
    }
  },
  methods: {
    updateItem() {
      this.\$emit('update', this.editedItem);
    },
    getInputType(type) {
      // Map your schema types to HTML input types here
      switch (type) {
        case 'string':
          return 'text';
        case 'number':
          return 'number';
        case 'email':
          return 'email';
        // Add more cases as needed
        default:
          return 'text'; // Fallback
      }
    }
  }
};
</script>
VUE;

    return trim($content); // Clean up whitespace
  }

  public function generateDeleteComponent($model, $schema)
  {
    $content = "<template>
  <div class=\"max-w-2xl mx-auto p-6\">
    <div class=\"bg-white rounded-lg shadow-md p-6\">
      <h2 class=\"text-2xl font-bold mb-4\">Delete " . $model . "</h2>
      <p class=\"text-gray-600 mb-6\">Are you sure you want to delete this {$model}?</p>
      <div class=\"flex justify-end space-x-4\">
        <button @click='confirmDelete' class=\"bg-red-500 text-white py-2 px-4 rounded-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2\">Confirm Delete</button>
        <button @click='cancelDelete' class=\"bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2\">Cancel</button>
      </div>
    </div>
  </div>
</template>";

    // Keep existing script
    $content .= "\n<script>
export default {
  props: ['item'],
  methods: {
    confirmDelete() {
      this.\$emit('confirm');
    },
    cancelDelete() {
      this.\$emit('cancel');
    }
  }
};
</script>";

    return $content;
  }

  public function generateMainComponent($model)
  {
    $modelLower = strtolower($model);
    return <<<VUE
<template>
  <div class="container mx-auto p-4">
    <div v-if="store.loading" class="text-center py-4">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900 mx-auto"></div>
    </div>

    <div v-else-if="store.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
      {{ store.error }}
    </div>

    <div v-else>
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">{$model} Management</h1>
        <button 
          @click="showCreate = true"
          class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
        >
          Create New {$model}
        </button>
      </div>

      <{$model}List 
        v-if="!showCreate && !showUpdate && !showDelete"
        :items="store.items"
        @edit="editItem"
        @delete="deleteItem"
      />
      
      <{$model}Create 
        v-if="showCreate"
        @create="createItem"
        @cancel="showCreate = false"
      />
      
      <{$model}Update 
        v-if="showUpdate"
        :item="store.selectedItem"
        @update="updateItem"
        @cancel="showUpdate = false"
      />
      
      <{$model}Delete 
        v-if="showDelete"
        :item="store.selectedItem"
        @confirm="confirmDelete"
        @cancel="showDelete = false"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { use{$model}Store } from '@/stores/{$modelLower}Store';
import {$model}List from './{$model}List.vue';
import {$model}Create from './{$model}Create.vue';
import {$model}Update from './{$model}Update.vue';
import {$model}Delete from './{$model}Delete.vue';

const store = use{$model}Store();
const showCreate = ref(false);
const showUpdate = ref(false);
const showDelete = ref(false);

onMounted(async () => {
  await store.fetchAll();
});

const createItem = async (item) => {
  try {
    await store.create(item);
    showCreate.value = false;
  } catch (error) {
    console.error('Failed to create item:', error);
  }
};

const editItem = async (item) => {
  await store.fetchById(item.id);
  showUpdate.value = true;
};

const updateItem = async (item) => {
  try {
    await store.update(item.id, item);
    showUpdate.value = false;
  } catch (error) {
    console.error('Failed to update item:', error);
  }
};

const deleteItem = (item) => {
  store.selectedItem = item;
  showDelete.value = true;
};

const confirmDelete = async () => {
  try {
    await store.delete(store.selectedItem.id);
    showDelete.value = false;
  } catch (error) {
    console.error('Failed to delete item:', error);
  }
};
</script>
VUE;
  }

  public function getFileExtension()
  {
    return 'vue';
  }

  /*private function getInputType($type)
  {
    switch ($type) {
      case 'integer':
      case 'float':
        return 'number';
      case 'text':
        return 'textarea';
      default:
        return 'text';
    }
  }*/

  public function updateRouting($projectPath, $model)
  {
    $routesJsonFile = $projectPath . '/src/routes.json';
    $routerFile = $projectPath . '/src/router/index.js';

    // Ensure the router directory exists
    if (!is_dir(dirname($routerFile))) {
      mkdir(dirname($routerFile), 0777, true);
    }

    // Load existing routes or create new array
    $routes = $this->loadRoutesFromJson($routesJsonFile);


    // Add new route if it doesn't exist
    $newRoute = $this->generateRouteForModel($model);


    if (!$this->routeExists($routes, $model)) {
      $routes[] = $newRoute;
      Yii::info("New route added for model: " . $model, __METHOD__);
    } else {
      Yii::info("Route already exists for model: " . $model, __METHOD__);
    }

    // Save updated routes to JSON file
    $routesJsonContent = json_encode($routes, JSON_PRETTY_PRINT);
    file_put_contents($routesJsonFile, $routesJsonContent);
    Yii::info("Routes JSON file updated: " . $routesJsonFile, __METHOD__);


    // Generate router file content
    $routerContent = $this->generateRouterFileContent($routes);
    file_put_contents($routerFile, $routerContent);
    Yii::info("Router file updated: " . $routerFile, __METHOD__);

    // Return both files as CodeFile objects
    return [
      new CodeFile($routesJsonFile, $routesJsonContent),
      new CodeFile($routerFile, $routerContent)
    ];
  }

  private function loadRoutesFromJson($filePath)
  {
    if (file_exists($filePath)) {
      $jsonContent = file_get_contents($filePath);
      $routes = json_decode($jsonContent, true);
      return is_array($routes) ? $routes : [];
    }
    return [];
  }

  private function routeExists($routes, $model)
  {
    foreach ($routes as $route) {
      if ($route['path'] === '/' . $model) {
        return true;
      }
    }
    return false;
  }

  private function generateRouteForModel($model)
  {

    $lowerModel = strtolower($model);
    return [
      'path' => '/' . $lowerModel,
      'name' => $lowerModel,
      'component' => "import('@/components/{$lowerModel}/{$model}Main.vue')",
      'children' => [
        [
          'path' => '',
          'name' => "{$lowerModel}List",
          'component' => "import('@/components/{$lowerModel}/{$model}List.vue')"
        ],
        [
          'path' => 'create',
          'name' => "{$lowerModel}Create",
          'component' => "import('@/components/{$lowerModel}/{$model}Create.vue')"
        ],
        [
          'path' => 'update/:id',
          'name' => "{$lowerModel}Update",
          'component' => "import('@/components/{$lowerModel}/{$model}Update.vue')"
        ],
        [
          'path' => 'delete/:id',
          'name' => "{$lowerModel}Delete",
          'component' => "() => import('@/components/{$lowerModel}/{$model}Delete.vue')"
        ]
      ]
    ];
  }

  private function generateRouterFileContent($routes)
  {
    $imports = "import { createRouter, createWebHistory } from 'vue-router'\n";
    $routeDefinitions = "";


    foreach ($routes as $route) {

      //$imports .= " {$route['component']}";
      /*foreach ($route['children'] as $child) {

        $imports .= "import {$child['component']} from '@/components/{$child['name']}/{$child['component']}.vue'\n";
      }*/

      $routeDefinitions .= $this->generateRouteDefinition($route);
    }




    return <<<JS
$imports

const routes = [
$routeDefinitions
]

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes
})

export default router
JS;

  }

  private function generateRouteDefinition($route)
  {
    $childrenStr = '';
    if (!empty($route['children'])) {
      $childrenStr = "    children: [\n";
      foreach ($route['children'] as $child) {
        $childrenStr .= <<<JS
      {
        path: '{$child['path']}',
        name: '{$child['name']}',
        component: () => {$child['component']}
      },\n
JS;
      }
      $childrenStr .= "    ],\n";
    }

    return <<<JS
  {
    path: '{$route['path']}',
    name: '{$route['name']}',
    component: {$route['component']},
{$childrenStr}  },

JS;
  }

  private function generateComponentTemplate($model)
  {
    $lowerModel = strtolower($model);
    $fixtures = DatabaseFixtures::getFixtures();
    $modelFixture = reset($fixtures[$lowerModel]);

    $formFields = '';
    foreach ($modelFixture as $field => $value) {
      if ($field === 'id')
        continue;

      $inputType = $this->getInputType($value);
      if ($inputType === 'textarea') {
        $formFields .= $this->generateTextareaField($field);
      } else {
        $formFields .= $this->generateInputField($field, $inputType);
      }
    }

    return <<<VUE
<template>
  <div class="update-form">
    <h2>Update {$model}</h2>
    <form @submit.prevent="updateItem" class="form">
      {$formFields}
      <button type="submit" class="submit-btn">Update {$model}</button>
    </form>
  </div>
</template>

<script>
export default {
  props: {
    item: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      formData: {
        {$this->generateDataProperties($modelFixture)}
      }
    }
  },
  created() {
    this.formData = { ...this.item }
  },
  methods: {
    updateItem() {
      this.\$emit('update', this.formData)
    }
  }
}
</script>

<style scoped>
.update-form {
  max-width: 500px;
  margin: 0 auto;
  padding: 20px;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

label {
  font-weight: bold;
}

input, textarea {
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 16px;
}

textarea {
  resize: vertical;
}

.submit-btn {
  padding: 10px 20px;
  background-color: #4CAF50;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 16px;
  transition: background-color 0.3s;
}

.submit-btn:hover {
  background-color: #45a049;
}
</style>
VUE;
  }

  private function getInputType($type)
  {
    switch ($type) {
      case 'integer':
      case 'bigint':
      case 'smallint':
        return 'number';
      case 'decimal':
      case 'float':
      case 'double':
        return 'number';
      case 'boolean':
        return 'checkbox';
      case 'date':
        return 'date';
      case 'datetime':
        return 'datetime-local';
      case 'time':
        return 'time';
      case 'email':
        return 'email';
      case 'password':
        return 'password';
      case 'url':
        return 'url';
      case 'text':
      case 'longtext':
      case 'mediumtext':
        return 'textarea';
      default:
        return 'text';
    }
  }

  private function generateInputField($field, $inputType)
  {
    $label = ucfirst($field);
    return <<<HTML
      <div class="form-group">
        <label for="{$field}">{$label}</label>
        <input 
          id="{$field}"
          v-model.trim="formData.{$field}"
          type="{$inputType}"
          required
        >
      </div>

HTML;
  }

  private function generateTextareaField($field)
  {
    $label = ucfirst($field);
    return <<<HTML
      <div class="form-group">
        <label for="{$field}">{$label}</label>
        <textarea 
          id="{$field}"
          v-model.trim="formData.{$field}"
          rows="4"
          required
        ></textarea>
      </div>

HTML;
  }

  private function generateDataProperties($modelFixture)
  {
    $properties = [];
    foreach ($modelFixture as $field => $value) {
      if ($field === 'id')
        continue;
      $defaultValue = is_numeric($value) ? '0' : "''";
      $properties[] = "{$field}: {$defaultValue}";
    }
    return implode(",\n        ", $properties);
  }

  private function verifyTailwindInstallation($projectPath)
  {
    $requiredFiles = [
      '/tailwind.config.js',
      '/src/assets/main.css',
      '/postcss.config.js'
    ];

    foreach ($requiredFiles as $file) {
      if (!file_exists($projectPath . $file)) {
        Yii::error("Missing required file: {$file}", __METHOD__);
        return false;
      }
    }
    // Check if Tailwind is properly imported in main.js
    $mainJs = file_get_contents($projectPath . '/src/main.js');
    if (strpos($mainJs, './assets/main.css') === false) {
      Yii::error("Tailwind CSS import missing in main.js", __METHOD__);
      return false;
    }
    return true;
  }



  public function generateStore($model)
  {
    $modelLower = strtolower($model);
    return <<<JS
// src/stores/{$modelLower}Store.js
import { defineStore } from 'pinia';
import { {$modelLower}Api } from '@/services/{$modelLower}Api';
import { faker } from '@faker-js/faker';

export const use{$model}Store = defineStore('{$modelLower}', {
    state: () => ({
        items: [],
        loading: false,
        error: null,
        selectedItem: null
    }),

    actions: {
        generateFakeData() {
            return {
                title: faker.commerce.productName(),
                description: faker.commerce.productDescription(),
                price: faker.commerce.price(),
            };
        },

        async fetchAll() {
            this.loading = true;
            try {
                const response = await {$modelLower}Api.getAll();
                this.items = response.data;
            } catch (error) {
                this.error = error.message;
                console.error('Error fetching {$modelLower}s:', error);
            } finally {
                this.loading = false;
            }
        },

        async fetchById(id) {
            this.loading = true;
            try {
                const response = await {$modelLower}Api.getById(id);
                this.selectedItem = response.data;
            } catch (error) {
                this.error = error.message;
                console.error('Error fetching {$modelLower}:', error);
            } finally {
                this.loading = false;
            }
        },

        async create(data) {
            this.loading = true;
            try {
                const fakeData = this.generateFakeData();
                const response = await {$modelLower}Api.create({ ...fakeData, ...data });
                this.items.unshift(response.data);
                return response.data;
            } catch (error) {
                this.error = error.message;
                console.error('Error creating {$modelLower}:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async update(id, data) {
            this.loading = true;
            try {
                const response = await {$modelLower}Api.update(id, data);
                const index = this.items.findIndex(item => item.id === id);
                if (index !== -1) {
                    this.items[index] = response.data;
                }
                return response.data;
            } catch (error) {
                this.error = error.message;
                console.error('Error updating {$modelLower}:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async delete(id) {
            this.loading = true;
            try {
                await {$modelLower}Api.delete(id);
                this.items = this.items.filter(item => item.id !== id);
            } catch (error) {
                this.error = error.message;
                console.error('Error deleting {$modelLower}:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        }
    }
});
JS;
  }

  public function generateAll($model, $schema, $projectPath)
  {
    // 1. Create necessary directories
    $directories = [
      $projectPath . '/src/components/' . strtolower($model),
      $projectPath . '/src/stores',
      $projectPath . '/src/services',
      $projectPath . '/src/router'
    ];

    foreach ($directories as $dir) {
      if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
      }
    }

    // 2. Generate all files
    $files = [];

    // API Service
    $files[] = new CodeFile(
      $projectPath . '/src/services/' . strtolower($model) . 'Api.js',
      $this->generateApiService($model)
    );

    // Pinia Store
    $files[] = new CodeFile(
      $projectPath . '/src/stores/' . strtolower($model) . 'Store.js',
      $this->generateStore($model)
    );

    // Pinia Setup
    $files[] = new CodeFile(
      $projectPath . '/src/stores/index.js', <<<JS
import { createPinia } from 'pinia'
export const pinia = createPinia()
JS
    );

    // Components
    $componentFiles = [
      'Main' => $this->generateMainComponent($model),
      'List' => $this->generateListComponent($model, $schema),
      'Create' => $this->generateCreateComponent($model, $schema),
      'Update' => $this->generateUpdateComponent($model, $schema),
      'Delete' => $this->generateDeleteComponent($model, $schema)
    ];

    foreach ($componentFiles as $type => $content) {
      $files[] = new CodeFile(
        $projectPath . '/src/components/' . strtolower($model) . '/' . $model . $type . '.vue',
        $content
      );
    }

    // Update main.js
    $files[] = new CodeFile(
      $projectPath . '/src/main.js', <<<JS
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { pinia } from './stores'
import './assets/main.css'

const app = createApp(App)
app.use(pinia)
app.use(router)
app.mount('#app')
JS
    );

    // Update package.json to add dependencies
    $packageJsonPath = $projectPath . '/package.json';
    if (file_exists($packageJsonPath)) {
      $packageJson = json_decode(file_get_contents($packageJsonPath), true);
      $packageJson['dependencies'] = array_merge($packageJson['dependencies'] ?? [], [
        'axios' => '^1.6.2',
        'pinia' => '^2.1.7',
        '@faker-js/faker' => '^8.3.1'
      ]);
      $files[] = new CodeFile($packageJsonPath, json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    // Add route configuration
    $routeFiles = $this->updateRouting($projectPath, $model);
    $files = array_merge($files, $routeFiles);

    // Install dependencies
    //  $this->executeCommand('npm install axios pinia @faker-js/faker', $projectPath);

    return $files;
  }

  public function generateAxiosInstance($model)
  {
    return <<<JS
import axios from 'axios';

// Create axios instance with custom config
const api = axios.create({
    baseURL: 'https://jsonplaceholder.typicode.com',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// Request interceptor
api.interceptors.request.use(
    (config) => {
        // Add any request interceptors here
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Response interceptor
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response) {
            // Handle specific error codes
            switch (error.response.status) {
                case 401:
                    console.error('Unauthorized');
                    break;
                case 404:
                    console.error('Not found');
                    break;
                default:
                    console.error('API Error:', error.response.data);
            }
        } else if (error.request) {
            console.error('Network Error');
        } else {
            console.error('Error:', error.message);
        }
        return Promise.reject(error);
    }
);

export default api;
JS;
  }

  public function generateApiService($model)
  {
    $modelLower = strtolower($model);
    return <<<JS
import api from './axios';

export const {$modelLower}Service = {
    async getAll() {
        try {
            const response = await api.get('/{$modelLower}s');
            return response.data;
        } catch (error) {
            console.error('Error fetching {$modelLower}s:', error);
            throw error;
        }
    },

    async getById(id) {
        try {
            const response = await api.get(`/{$modelLower}s/\${id}`);
            return response.data;
        } catch (error) {
            console.error('Error fetching {$modelLower}:', error);
            throw error;
        }
    },

    async create(data) {
        try {
            const response = await api.post('/{$modelLower}s', data);
            return response.data;
        } catch (error) {
            console.error('Error creating {$modelLower}:', error);
            throw error;
        }
    },

    async update(id, data) {
        try {
            const response = await api.put(`/{$modelLower}s/\${id}`, data);
            return response.data;
        } catch (error) {
            console.error('Error updating {$modelLower}:', error);
            throw error;
        }
    },

    async delete(id) {
        try {
            await api.delete(`/{$modelLower}s/\${id}`);
            return true;
        } catch (error) {
            console.error('Error deleting {$modelLower}:', error);
            throw error;
        }
    }
};
JS;
  }

  private function generatePiniaSetup()
  {
    return <<<JS
import { createPinia } from 'pinia'
export const pinia = createPinia()
JS;
  }

}