<?php
namespace StackedSpawners\UI;

use pocketmine\player\Player;
use pocketmine\block\Block;
use jojoe77777\FormAPI\CustomForm;
use StackedSpawners\Data\SpawnerDataManager;
use pocketmine\utils\TextFormat as TF;

class RemoveAmountUI {
    public function __construct(
        private Player $player,
        private Block $spawner,
        private SpawnerDataManager $dataManager,
        private string $type
    ) {
        $this->sendForm();
    }

    private function sendForm(): void {
        $max = $this->dataManager->getSpawnerData($this->spawner->getPosition())['types'][$this->type] ?? 0;
        
        $form = new CustomForm(function (Player $player, ?array $data) use ($max) {
            if ($data !== null) {
                $amount = min((int)$data[0], $max);
                $this->handleRemoval($amount);
            }
        });

        $form->setTitle(TF::BOLD . "Remove " . ucfirst($this->type));
        $form->addInput(
            "Enter amount to remove:",
            "Max: $max",
            "1"
        );
        
        $this->player->sendForm($form);
    }

    private function handleRemoval(int $amount): void {
        if ($amount > 0) {
            $spawnerData = $this->dataManager->getSpawnerData($this->spawner->getPosition());
            $spawnerData['types'][$this->type] -= $amount;
            
            if ($spawnerData['types'][$this->type] <= 0) {
                unset($spawnerData['types'][$this->type]);
            }
            
            $this->dataManager->updateSpawnerData($this->spawner->getPosition(), $spawnerData);
            $this->giveSpawners($amount);
            $this->player->sendMessage(TF::GREEN . "Removed $amount " . $this->type . " spawner(s)!");
        }
    }

    private function giveSpawners(int $amount): void {
        $spawnerItem = BlockFactory::getInstance()
            ->get(BlockTypeIds::MONSTER_SPAWNER)
            ->asItem()
            ->setCount($amount);
        
        $nbt = new CompoundTag();
        $nbt->setTag(Main::SPAWNER_DATA_KEY, new CompoundTag());
        $nbt->getCompoundTag(Main::SPAWNER_DATA_KEY)
            ->setString("type", $this->type)
            ->setInt("count", $amount);
        
        $spawnerItem->setNamedTag($nbt);
        $spawnerItem->setCustomName(TF::RESET . ucfirst($this->type) . " Spawner");
        
        $this->player->getInventory()->addItem($spawnerItem);
    }
}