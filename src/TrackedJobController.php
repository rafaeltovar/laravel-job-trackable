<?php
namespace LaravelJobTrackable;

use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Imtigger\LaravelJobStatus\JobStatus;
use Imtigger\LaravelJobStatus\JobStatusUpdater;

class TrackedJobController
{
    protected $redis;
    protected $prefix;
    protected $expire;

    public function __construct($redis, string $prefix, int $expire)
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
        $this->expire = $expire;
    }

    protected function getRedis()
    {
        return $this->redis;
    }

    protected function save(TrackedJob $tracked)
    {
        $key = sprintf("%s:%s", $this->prefix, $tracked->getId());
        return $this->getRedis()->setex($key, $this->expire, serialize($tracked));
    }

    protected function get(string $id) : ?TrackedJob
    {
        $key = sprintf("%s:%s", $this->prefix, $id);
        $value = $this->getRedis()->get($key);

        return isset($value)? unserialize($value) : null;
    }

    public function start(string $type, array $input = []) : TrackedJob
    {
        $value = 1;
        while(isset($value))
        {
            $tracked = new TrackedJob($type, $input);
            $value = $this->get($tracked->getId());
        }

        $this->save($tracked);

        return $tracked;
    }

    protected function getJobFromEvent($event)
    {
        try {
            $payload = $event->job->payload();

            return unserialize($payload['data']['command']);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return null;
        }
    }

    protected function getJobTrackId($job)
    {
        if (method_exists($job, 'getTrackId')) {
            return $job->getTrackId();
        }

        return null;
    }

    public function statusUpdate($event, string $status) : void
    {
        $job = $this->getJobFromEvent($event);
        $id = $this->getJobTrackId($job);

        if(isset($id)) {
            $tracked = $this->get($id);

            if(!isset($tracked))
                throw new \Exception(sprintf("Tracked job with id '%s' not found!", $id));

            $tracked->setStatus($status);

            $this->save($tracked);
        }
    }

    public function before(JobProcessing $event) : void
    {
        $this->statusUpdate($event, TrackedJob::STATUS_EXECUTING);
    }

    public function after(JobProcessed $event): void
    {
        $this->statusUpdate($event, TrackedJob::STATUS_FINISHED);
    }

    public function failing(JobFailed $event): void
    {
        $this->statusUpdate($event, TrackedJob::STATUS_FAILED);
    }

    public function exceptionOccurred(JobExceptionOccurred $event): void
    {
        $this->statusUpdate($event, TrackedJob::STATUS_FAILED);
    }


}
