<?php
namespace StackedSpawners\Tasks;

use pocketmine\scheduler\Task;
use StackedSpawners\Data\SpawnerDataManager;
use StackedSpawners\Main;
use pocketmine\utils\TextFormat as TF;

class OfflineProcessingTask extends Task {
    private const BATCH_SIZE = 50; // Process 50 spawners per tick
    
    private int $lastProcessedIndex = 0;
    private array $spawnerKeys = [];

    public function __construct(
        private SpawnerDataManager $dataManager
    ) {
        $this->refreshSpawnerList();
    }

    public function onRun(): void {
        try {
            $this->processInBatches();
        } catch (\Throwable $e) {
            Main::getInstance()->getLogger()->error("Offline processing error: " . $e->getMessage());
        }
    }

    private function processInBatches(): void {
        $total = count($this->spawnerKeys);
        if ($total === 0) return;

        $processed = 0;
        $startTime = microtime(true);
        
        while ($processed < self::BATCH_SIZE && $this->lastProcessedIndex < $total) {
            $key = $this->spawnerKeys[$this->lastProcessedIndex];
            $this->dataManager->processSingleSpawner($key);
            
            $this->lastProcessedIndex++;
            $processed++;
            
            // Prevent blocking the main thread
            if ((microtime(true) - $startTime) * 1000 > 2.5) break;
        }

        // Refresh list when complete
        if ($this->lastProcessedIndex >= $total) {
            $this->refreshSpawnerList();
        }
    }

    private function refreshSpawnerList(): void {
        $this->spawnerKeys = $this->dataManager->getAllSpawnerKeys();
        $this->lastProcessedIndex = 0;
        
        if (count($this->spawnerKeys) > 0) {
            Main::getInstance()->getLogger()->debug(TF::GRAY . "Processing " . 
                count($this->spawnerKeys) . " offline spawners");
        }
    }
}