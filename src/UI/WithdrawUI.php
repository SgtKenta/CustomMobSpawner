<?php
namespace StackedSpawners\UI;

use pocketmine\player\Player;
use pocketmine\block\Block;
use jojoe77777\FormAPI\CustomForm;
use StackedSpawners\Data\SpawnerDataManager;
use pocketmine\utils\TextFormat as TF;

class WithdrawUI {
    public function __construct(
        private Player $player,
        private Block $spawner,
        private SpawnerDataManager $dataManager,
        private string $type
    ) {
        $this->sendForm();
    }

    private function sendForm(): void {
        $form = new CustomForm(function (Player $player, ?array $data) {
            if ($data !== null) {
                $this->handleWithdraw((int)$data[0]);
            }
        });

        $current = $this->dataManager->getSpawnerData($this->spawner->getPosition())[$this->type];
        $form->setTitle(TF::BOLD . "Withdraw " . ucfirst($this->type));
        $form->addInput(
            "Enter amount to withdraw:",
            "Max: $current",
            (string)min($current, 64)
        );
        
        $this->player->sendForm($form);
    }

    private function handleWithdraw(int $amount): void {
        $amount = max(0, min($amount, $this->getMaxWithdrawable()));
        
        if ($amount > 0) {
            $this->updateSpawnerData($amount);
            $this->giveResources($amount);
            $this->player->sendMessage(TF::GREEN . "Withdrew $amount " . $this->type);
        }
    }

    private function getMaxWithdrawable(): int {
        return $this->dataManager->getSpawnerData($this->spawner->getPosition())[$this->type];
    }

    private function updateSpawnerData(int $amount): void {
        $data = $this->dataManager->getSpawnerData($this->spawner->getPosition());
        $data[$this->type] -= $amount;
        $this->dataManager->updateSpawnerData($this->spawner->getPosition(), $data);
    }

    private function giveResources(int $amount): void {
        // Implement resource distribution based on type
        // Example for loot:
        if ($this->type === 'loot') {
            $this->player->getInventory()->addItem(
                ItemFactory::getInstance()->get(ItemIds::GOLD_INGOT, 0, $amount)
            );
        }
    }
}