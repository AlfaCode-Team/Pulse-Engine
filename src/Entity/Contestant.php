<?php
namespace AlfacodeTeam\PulseEngine\Entity;

class Contestant {
    public $id;
    public $fullName;
    public $votes;
    public $editionId;
    public $status;

    public function __construct(array $data) {
        $this->id = $data["ID"] ?? null;
        $this->fullName = $data["FullName"] ?? "";
        $this->votes = (int)($data["Votes"] ?? 0);
        $this->editionId = (int)($data["EditionID"] ?? 0);
        $this->status = $data["Status"] ?? "active";
    }

    public function isActive(): bool {
        return $this->status === "active";
    }
}