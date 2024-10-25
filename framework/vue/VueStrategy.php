<?php

namespace app\modules\ui\generator\framework\vue;

use app\modules\ui\generator\framework\FrameworkStrategy;
use yii\gii\CodeFile;
use Yii;

class VueStrategy implements FrameworkStrategy
{
    public function runCommand($viewName, $projectPath)
    {
        $npmCommand = 'vue create ' . escapeshellarg($viewName) . ' --default';
        $this->executeCommand($npmCommand, $projectPath);
    }

    private function executeCommand($npmCommand, $projectPath)
    {
        $command = "start cmd /k \"cd $projectPath && $npmCommand && pause\"";
        exec($command);
    }

    public function generateListComponent($model, $schema)
    {
        $content = "<template>
  <div>
    <h2>{$model} List</h2>
    <table>
      <thead>
        <tr>\n";
        foreach ($schema as $attribute => $type) {
            $content .= "          <th>{$attribute}</th>\n";
        }
        $content .= "          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for='item in items' :key='item.id'>
";
        foreach ($schema as $attribute => $type) {
            $content .= "          <td>{{ item.{$attribute} }}</td>\n";
        }
        $content .= "          <td>
            <button @click='editItem(item)'>Edit</button>
            <button @click='deleteItem(item)'>Delete</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
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
  <div>
    <h2>Create {$model}</h2>
    <form @submit.prevent='createItem'>
";
        foreach ($schema as $attribute => $type) {
            $content .= "      <div>
        <label>{$attribute}</label>
        <input v-model='item.{$attribute}' type='" . $this->getInputType($type) . "'>
      </div>\n";
        }
        $content .= "      <button type='submit'>Create</button>
    </form>
  </div>
</template>

<script>
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
        $content = "<template>
  <div>
    <h2>Update {$model}</h2>
    <form @submit.prevent='updateItem'>
";
        foreach ($schema as $attribute => $type) {
            $content .= "      <div>
        <label>{$attribute}</label>
        <input v-model='item.{$attribute}' type='" . $this->getInputType($type) . "'>
      </div>\n";
        }
        $content .= "      <button type='submit'>Update</button>
    </form>
  </div>
</template>

<script>
export default {
  props: ['item'],
  methods: {
    updateItem() {
      this.\$emit('update', this.item);
    }
  }
};
</script>";

        return $content;
    }

    public function generateDeleteComponent($model, $schema)
    {
        $content = "<template>
  <div>
    <h2>Delete {$model}</h2>
    <p>Are you sure you want to delete this {$model}?</p>
    <button @click='confirmDelete'>Confirm Delete</button>
    <button @click='cancelDelete'>Cancel</button>
  </div>
</template>

<script>
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
        $content = "<template>
  <div>
    <h1>{$model} Management</h1>
    <button @click='showCreate = true'>Create New {$model}</button>
    
    <{$model}List 
      v-if='!showCreate && !showUpdate && !showDelete'
      @edit='editItem'
      @delete='deleteItem'
    />
    
    <{$model}Create 
      v-if='showCreate'
      @create='createItem'
    />
    
    <{$model}Update 
      v-if='showUpdate'
      :item='selectedItem'
      @update='updateItem'
    />
    
    <{$model}Delete 
      v-if='showDelete'
      :item='selectedItem'
      @confirm='confirmDelete'
      @cancel='cancelDelete'
    />
  </div>
</template>

<script>
import {$model}List from './{$model}List.vue';
import {$model}Create from './{$model}Create.vue';
import {$model}Update from './{$model}Update.vue';
import {$model}Delete from './{$model}Delete.vue';

export default {
  components: {
    {$model}List,
    {$model}Create,
    {$model}Update,
    {$model}Delete
  },
  data() {
    return {
      showCreate: false,
      showUpdate: false,
      showDelete: false,
      selectedItem: null
    };
  },
  methods: {
    createItem(item) {
      // Implement create logic
      console.log('Create', item);
      this.showCreate = false;
    },
    editItem(item) {
      this.selectedItem = item;
      this.showUpdate = true;
    },
    updateItem(item) {
      // Implement update logic
      console.log('Update', item);
      this.showUpdate = false;
    },
    deleteItem(item) {
      this.selectedItem = item;
      this.showDelete = true;
    },
    confirmDelete() {
      // Implement delete logic
      console.log('Delete', this.selectedItem);
      this.showDelete = false;
    },
    cancelDelete() {
      this.showDelete = false;
    }
  }
};
</script>";

        return $content;
    }

    public function getFileExtension()
    {
        return 'vue';
    }

    private function getInputType($type)
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
    }

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
            'component' => "{$model}Main",
            'children' => [
                [
                    'path' => '',
                    'name' => "{$lowerModel}List",
                    'component' => "{$model}List"
                ],
                [
                    'path' => 'create',
                    'name' => "{$lowerModel}Create",
                    'component' => "{$model}Create"
                ],
                [
                    'path' => 'update/:id',
                    'name' => "{$lowerModel}Update",
                    'component' => "{$model}Update"
                ],
                [
                    'path' => 'delete/:id',
                    'name' => "{$lowerModel}Delete",
                    'component' => "{$model}Delete"
                ]
            ]
        ];
    }

    private function generateRouterFileContent($routes)
    {
        $imports = "import { createRouter, createWebHistory } from 'vue-router'\n";
        $routeDefinitions = "";

        foreach ($routes as $route) {
            $imports .= "import {$route['component']} from '@/components/{$route['name']}/{$route['component']}.vue'\n";
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
        $children = '';
        if (!empty($route['children'])) {
            $children = "    children: [\n";
            foreach ($route['children'] as $child) {
                $children .= "      {\n";
                $children .= "        path: '{$child['path']}',\n";
                $children .= "        name: '{$child['name']}',\n";
                $children .= "        component: () => import('@/components/{$route['name']}/{$child['component']}.vue')\n";
                $children .= "      },\n";
            }
            $children .= "    ]\n";
        }

        return <<<JS
  {
    path: '{$route['path']}',
    name: '{$route['name']}',
    component: {$route['component']},
$children  },

JS;
    }
}
