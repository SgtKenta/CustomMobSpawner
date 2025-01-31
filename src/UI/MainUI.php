<?php
namespace StackedSpawners\UI;

use pocketmine\player\Player;
use pocketmine\block\Block;
use jojoe77777\FormAPI\SimpleForm;
use StackedSpawners\Main;
use StackedSpawners\Data\SpawnerDataManager;
use StackedSpawners\UI\WithdrawUI;
use StackedSpawners\UI\RemoveUI;
use pocketmine\utils\TextFormat as TF;

class MainUI {
    private const BUTTON_COLORS = [
        'loot' => TF::GOLD,
        'xp' => TF::LIGHT_PURPLE,
        'remove' => TF::RED
    ];

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
                $this->handleResponse($data);
            }
        });

        $spawnerData = $this->dataManager->getSpawnerData($this->spawner->getPosition());
        $form->setTitle($this->getFormTitle($spawnerData));
        $form->setContent($this->getFormContent($spawnerData));
        
        $this->addButtons($form);
        $this->player->sendForm($form);
    }

    private function getFormTitle(array $data): string {
        $total = array_sum($data['types'] ?? []);
        return TF::BOLD . TF::GOLD . "Stacked Spawner" . TF::EOL .
               TF::RESET . TF::GRAY . "(" . $total . " combined)";
    }

    private function getFormContent(array $data): string {
        return TF::BOLD . TF::GREEN . "Loot: " . TF::RESET . $data['loot'] . TF::EOL .
               TF::BOLD . TF::DARK_PURPLE . "XP: " . TF::RESET . $data['xp'];
    }

    private function addButtons(SimpleForm $form): void {
        $form->addButton(
            $this->formatButton("Withdraw Loot", 'loot'),
            0,
            "textures/items/gold_ingot"
        );
        
        $form->addButton(
            $this->formatButton("Withdraw XP", 'xp'),
            0,
            "textures/items/experience_bottle"
        );
        
        $form->addButton(
            $this->formatButton("Remove Spawners", 'remove'),
            0,
            "textures/blocks/barrier"
        );
    }

    private function formatButton(string $text, string $type): string {
        return self::BUTTON_COLORS[$type] . TF::BOLD . $text . "\n" .
               TF::RESET . TF::GRAY . "Click to manage";
    }

    private function handleResponse(int $buttonId): void {
        match ($buttonId) {
            0 => new WithdrawUI($this->player, $this->spawner, $this->dataManager, 'loot'),
            1 => new WithdrawUI($this->player, $this->spawner, $this->dataManager, 'xp'),
            2 => new RemoveUI($this->player, $this->spawner, $this->dataManager),
            default => null
        };
    }
}