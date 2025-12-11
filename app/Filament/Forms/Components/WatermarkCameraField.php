<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class WatermarkCameraField extends Field
{
    protected string $view = 'filament.forms.components.watermark-camera-field';

    protected string $photoType = 'before';
    protected mixed $lokasiId = null;
    protected mixed $activityReportId = null;
    protected int $maxPhotos = 5;

    public function photoType(string $type): static
    {
        $this->photoType = $type;

        return $this;
    }

    public function lokasiId(mixed $id): static
    {
        $this->lokasiId = $id;

        return $this;
    }

    public function activityReportId(mixed $id): static
    {
        $this->activityReportId = $id;

        return $this;
    }

    public function maxPhotos(int $max): static
    {
        $this->maxPhotos = $max;

        return $this;
    }

    public function getPhotoType(): string
    {
        return $this->photoType;
    }

    public function getLokasiId(): ?int
    {
        return $this->evaluate($this->lokasiId);
    }

    public function getActivityReportId(): ?int
    {
        return $this->evaluate($this->activityReportId);
    }

    public function getMaxPhotos(): int
    {
        return $this->maxPhotos;
    }
}
