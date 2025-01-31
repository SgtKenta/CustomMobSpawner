<?php
namespace StackedSpawners;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use pocketmine\item\Item;
use pocketmine\block\VanillaBlocks;
use pocketmine\nbt\tag\CompoundTag;
use StackedSpawners\Data\SpawnerDataManager;
use StackedSpawners\Events\SpawnerEventListener;
use StackedSpawners\Tasks\OfflineProcessingTask;

class Main extends PluginBase {
    private SpawnerDataManager $dataManager;
    public const SPAWNER_DATA_KEY = "StackedSpawnersData";
    public const PERMISSION_GIVE = "stackedspawners.command.give";

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->dataManager = new SpawnerDataManager($this);

        $this->getServer()->getPluginManager()->registerEvents(
            new SpawnerEventListener($this->dataManager),
            $this
        );

        $interval = $this->getConfig()->getInt("processing-interval", 1200);
        $this->getScheduler()->scheduleRepeatingTask(
            new OfflineProcessingTask($this->dataManager),
            $interval
        );

        $this->getLogger()->info(TF::GREEN . "StackedSpawners enabled!");
    }

    public function onDisable(): void {
        $this->dataManager->saveData();
        $this->getLogger()->info(TF::RED . "StackedSpawners disabled!");
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool {
        if ($cmd->getName() === "spawnergive") {
            // Permission check
            if (!$sender->hasPermission(self::PERMISSION_GIVE)) {
                $sender->sendMessage(TF::RED . "You don't have permission to use this command!");
                return true;
            }

            if (!$sender instanceof Player) {
                $sender->sendMessage(TF::RED . "This command must be used in-game!");
                return true;
            }

            if (count($args) < 1) {
                $sender->sendMessage(TF::RED . "Usage: /spawnergive <mobtype> [count]");
                return true;
            }

            $type = strtolower($args[0]);
            $count = min((int)($args[1] ?? 1), 64);

            $block = VanillaBlocks::MOB_SPAWNER()->asItem();
            $item->getNamedTag()->setTag(self::SPAWNER_DATA_KEY, CompoundTag::create()
                ->setString("type", $type)
                ->setInt("count", $count)
            );
            $item->setCustomName(TF::RESET . ucfirst($type) . " Spawner");

            $sender->getInventory()->addItem($item);
            $sender->sendMessage(TF::GREEN . "Given $count $type spawner(s)!");
            return true;
        }
        return false;
    }

    public function getDataManager(): SpawnerDataManager {
        return $this->dataManager;
    }
}