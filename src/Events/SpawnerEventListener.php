<?php
namespace StackedSpawners\Events;
 
use pocketmine\event\Listener;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\block\MonsterSpawner;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\utils\TextFormat as TF;
use StackedSpawners\Data\SpawnerDataManager;
use StackedSpawners\UI\MainUI;
 
class SpawnerEventListener implements Listener {
    private const STACK_RADIUS = 2;
    private const CACHE_TTL = 300; // 5 minutes
 
    private array $positionCache = [];
    private array $lastAccess = [];
 
    public function __construct(
        private SpawnerDataManager $dataManager
    ) {}
 
    public function onBlockPlace(BlockPlaceEvent $event): void {
        $item = $event->getItem();
        $block = $event->getBlock();
        $player = $event->getPlayer();
 
        if ($block instanceof MonsterSpawner && $item->getNamedTag()->getTag(Main::SPAWNER_DATA_KEY)) {
            $nbt = $item->getNamedTag()->getCompoundTag(Main::SPAWNER_DATA_KEY);
            $type = $nbt->getString("type");
            $count = $nbt->getInt("count");
 
            $this->dataManager->updateSpawnerData($block->getPosition(), [
                'types' => [$type => $count],
                'loot' => 0,
                'xp' => 0,
                'last_update' => time()
            ]);
 
            $this->updatePositionCache($block->getPosition());
            $player->sendMessage(TF::GREEN . "Placed $count $type spawner(s)!");
        }
    }
 
    public function onEntityDeath(EntityDeathEvent $event): void {
        $entity = $event->getEntity();
        $position = $entity->getPosition();
        $spawnerPos = $this->findNearestSpawner($position);
 
        if ($spawnerPos !== null) {
            $loot = count($event->getDrops());
            $xp = $event->getXpDropAmount();
 
            $this->dataManager->updateSpawnerData($spawnerPos, [
                'loot' => $loot,
                'xp' => $xp
            ]);
 
            $event->setDrops([]);
            $event->setXpDropAmount(0);
        }
    }
 
    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $item = $event->getItem();
 
        if ($block instanceof MonsterSpawner) {
            if ($event->getAction() === PlayerInteractEvent::LEFT_CLICK_BLOCK) {
                $this->handleStacking($player, $block, $item);
            } else {
                new MainUI($player, $block, $this->dataManager);
            }
            $event->cancel();
        }
    }
 
    private function handleStacking(Player $player, Block $block, Item $item): void {
        if ($item->getId() === ItemIds::MOB_SPAWNER && $item->getNamedTag()->getTag(Main::SPAWNER_DATA_KEY)) {
            $blockData = $this->dataManager->getSpawnerData($block->getPosition());
            $itemData = $item->getNamedTag()->getCompoundTag(Main::SPAWNER_DATA_KEY);
            $itemType = $itemData->getString("type");
            $itemCount = $itemData->getInt("count");
 
            $currentCount = $blockData['types'][$itemType] ?? 0;
            $blockData['types'][$itemType] = $currentCount + $itemCount;
 
            $this->dataManager->updateSpawnerData($block->getPosition(), $blockData);
            $item->pop();
            $player->getInventory()->setItemInHand($item);
 
            $this->updatePositionCache($block->getPosition());
            $player->sendMessage(TF::GREEN . "Added $itemCount $itemType spawner(s) to stack!");
        }
    }
 
    private function findNearestSpawner(Position $pos): ?Position {
        $currentTime = time();
        $this->cleanupCache();
 
        $closest = null;
        $minDistance = self::STACK_RADIUS + 1;
 
        foreach ($this->positionCache as $key => $_) {
            $spawnerPos = $this->dataManager->keyToPosition($key);
            $distance = $this->calculateDistance($pos, $spawnerPos);
 
            if ($distance < $minDistance && $pos->getWorld()->getFolderName() === $spawnerPos['world']) {
                $minDistance = $distance;
                $closest = new Position(
                    $spawnerPos['x'],
                    $spawnerPos['y'],
                    $spawnerPos['z'],
                    $pos->getWorld()
                );
            }
        }
 
        return $closest;
    }
 
    private function calculateDistance(Position $pos1, array $pos2): float {
        return sqrt(
            ($pos1->x - $pos2['x']) ** 2 +
            ($pos1->y - $pos2['y']) ** 2 +
            ($pos1->z - $pos2['z']) ** 2
        );
    }
 
    private function updatePositionCache(Position $pos): void {
        $key = $this->dataManager->positionToKey($pos);
        $this->positionCache[$key] = true;
        $this->lastAccess[$key] = time();
    }
 
    private function cleanupCache(): void {
        $now = time();
        foreach ($this->lastAccess as $key => $time) {
            if ($now - $time > self::CACHE_TTL) {
                unset($this->positionCache[$key], $this->lastAccess[$key]);
            }
        }
    }
    private const BATCH_SIZE = 50; // Process 50 spawners per tick

private function processInBatches(): void {
    // Spreads load across multiple ticks
    while ($processed < self::BATCH_SIZE && ...) {
        $this->dataManager->processSingleSpawner($key);
    }
}
}
