<?php

namespace Nucleus\Migration;

use Exception;
use Nucleus\IService\Application\IVariableRegistry;
use Nucleus\IService\DependencyInjection\IServiceContainer;
use Nucleus\IService\DependencyInjection\ServiceDoesNotExistsException;
use Nucleus\IService\Migration\IMigrationTask;
use Nucleus\IService\Migration\IMigrator;
use Nucleus\IService\Migration\MigrationTaskNotFoundException;
use RuntimeException;

class Migrator implements IMigrator
{
    /**
     *
     * @var array
     */
    private $configuration;

    /**
     * @var IServiceContainer 
     */
    private $serviceContainer;

    /**
     *
     * @var IVariableRegistry
     */
    private $applicationVariable;

    /**
     * 
     * @param \Nucleus\IService\DependencyInjection\IServiceContainer $serviceContainer
     * @param \Nucleus\IService\Application\IVariableRegistry $applicationVariable
     * 
     * @Inject
     */
    public function initialize(IServiceContainer $serviceContainer, IVariableRegistry $applicationVariable)
    {
        $this->serviceContainer = $serviceContainer;
        $this->applicationVariable = $applicationVariable;
    }

    /**
     * 
     * @param array $configuration
     * 
     * @Inject(configuration="$")
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;
    }

    public function runAll()
    {
        foreach ($this->configuration['versions'] as $version => $tasks) {
            foreach ($tasks as $task) {
                $migrationTask = $this->loadTask($task);
                $id = $version . ":" . $migrationTask->getUniqueId();

                if (!$this->applicationVariable->has($id)) {
                    $this->runTask($migrationTask, $id);
                }
            }
        }
    }

    public function markAllAsRun()
    {
        foreach ($this->configuration['versions'] as $version => $tasks) {
            foreach ($tasks as $task) {
                $migrationTask = $this->loadTask($task);
                $id = $version . ":" . $migrationTask->getUniqueId();
                $this->applicationVariable->set($id, true);
            }
        }
    }

    /**
     * 
     * @param string $task Task name
     * @return IMigrationTask of migration task to be executed
     */
    private function loadTask($task)
    {
        $taskName = $task['taskName'];
        try {
            $migrationTask = $this->serviceContainer->getServiceByName('migrationTask.' . $taskName);
            if(!($migrationTask instanceof IMigrationTask)) {
                throw new RuntimeException(
                    'The task [' . $taskName . '] does not implement the [\Nucleus\IService\Migration\IMigrationTask] interface'
                );
            }
            $parameters = array();
            if (isset($task['parameters'])) {
                $parameters = $task['parameters'];
            }
            $migrationTask->prepare($parameters);
            return $migrationTask;
        } catch (ServiceDoesNotExistsException $e) {
            throw new MigrationTaskNotFoundException("MigrationTask [" . $taskName . "] not found", null, $e);
        }
    }

    /**
     * @param IMigrationTask $task
     * @param string $id
     * @throws Exception
     */
    private function runTask(IMigrationTask $task, $id)
    {
        try {
            $task->run();
            $this->applicationVariable->set($id, true);
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * @param mixed $configuration
     * @return IMigrator
     */
    public static function factory($configuration = null)
    {
        if (is_null($configuration)) {
            $configuration = __DIR__ . '/nucleus.json';
        }

        return Nucleus::serviceFactory($configuration, self::NUCLEUS_SERVICE_NAME);
    }
}