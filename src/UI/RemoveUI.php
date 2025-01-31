<?php
namespace StackedSpawners\UI;

use pocketmine\player\Player;
use pocketmine\block\Block;
use jojoe77777\FormAPI\SimpleForm;
use StackedSpawners\Data\SpawnerDataManager;
use pocketmine\utils\TextFormat as TF;

class RemoveUI {
    public function __construct(
        private Player $player,
        private Block $spawner,
        private SpawnerDataManager $dataManager
    ) {
        $this->sendForm();
    }

    private function sendForm(): void {
        $form = new SimpleForm(function (Player $player, ?int $data) {
            if ($data !== null) {
                $this->handleSelection($data);
            }
        });

        $spawnerData = $this->dataManager->getSpawnerData($this->spawner->getPosition());
        $form->setTitle(TF::BOLD . TF::RED . "Remove Spawners");
        
        foreach ($spawnerData['types'] as $type => $count) {
            $form->addButton(
                TF::BOLD . ucfirst($type) . TF::RESET . TF::EOL .
                TF::GRAY . "Available: " . $count,
                0,
                $this->getMobIcon($type)
            );
        }
        
        $this->player->sendForm($form);
    }

    private function handleSelection(int $buttonId): void {
        $spawnerData = $this->dataManager->getSpawnerData($this->spawner->getPosition());
        $types = array_keys($spawnerData['types']);
        
        if (isset($types[$buttonId])) {
            $selectedType = $types[$buttonId];
            new RemoveAmountUI($this->player, $this->spawner, $this->dataManager, $selectedType);
        }
    }

    private function getMobIcon(string $type): string {
        return match (strtolower($type)) {
            'zombie' => "textures/entity/zombie",
            'skeleton' => "textures/entity/skeleton",
            'spider' => "textures/entity/spider",
            default => "textures/blocks/mob_spawner"
        };
    }
}