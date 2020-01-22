<?php
namespace LaravelJobTrackable\Jobs;

use LaravelJobTrackable\TrackedJobController;

trait TrackableJob
{
    protected $trackId;

    protected function track(array $input = [])
    {
       $ctrl = app(TrackedJobController::class);

       $tracked = $ctrl->start(static::class, $input);
       $this->trackId = $tracked->getId();
    }

    public function getTrackId() : ?string
    {
        return $this->trackId;
    }

    protected function setOutput(array $output = [])
    {
         $ctrl = app(TrackedJobController::class);
         $ctrl->setOutput($this->getTrackId(), $output);
    }
}
