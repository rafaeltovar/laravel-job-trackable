<?php
namespace LaravelJobTrackable;

class TrackedJob implements \JsonSerializable
{
    const STATUS_QUEUED = 'queued';
    const STATUS_EXECUTING = 'executing';
    const STATUS_FINISHED = 'finished';
    const STATUS_FAILED = 'failed';
    const STATUS_RETRYING = 'retrying';

    protected $id;
    protected $status;
    protected $input;
    protected $output;
    protected $tries;
    protected $type;
    protected $queuedAt;
    protected $startedAt;
    protected $retryAt;
    protected $finishedAt;

    public function __construct(string $type, array $input = [])
    {
        $factory = new \RandomLib\Factory;
        $generator = $factory->getGenerator(new \SecurityLib\Strength(\SecurityLib\Strength::MEDIUM));
        $this->id = $generator->generateString(6);
        $this->type = $type;
        $this->input = $input;
        $this->output = [];
        $this->status = self::STATUS_QUEUED;
        $this->queuedAt = time();
        $this->tries = 0;
    }

    public function getId() : string
    {
        return $this->id;
    }

    public function setStatus(string $status) : self
    {
        switch($status)
        {
            case self::STATUS_EXECUTING:
                if($this->retries==0)
                    $this->startedAt = time();
                else
                    $status = self::STATUS_RETRYING;

                $this->retryAt = time();
                $this->try();
                break;
            case self::STATUS_FINISHED:
            case self::FAILED:
                $this->finishedAt = time();
                break;

        }

        $this->status = $status;

        return $this;
    }

    public function getStatus() : string
    {
        return $this->status;
    }

    public function setOutput(array $output = []) : self
    {
        $this->output = $output;

        return $this;
    }

    public function getOutput() : array
    {
        return $this->output;
    }

    public function try() : self
    {
        $this->tries++;

        return $this;
    }

    protected function getDateTime($time) : ?\DateTime
    {
        if(isset($time))
            return new \DateTime(sprintf("@%s", $time));

        return null;
    }

    public function getQueuedAt() : ?\Datetime
    {
        return $this->getDateTime($this->queuedAt);
    }

    public function getStartedAt() : ?\Datetime
    {
        return $this->getDateTime($this->startedAt);
    }

    public function getRetryAt() : ?\Datetime
    {
        return $this->getDateTime($this->retryAt);
    }

    public function getFinishedAt() : ?\Datetime
    {
        return $this->getDateTime($this->finishedAt);
    }

    public function jsonSerialize() : mixed
    {
        return [
            'id' => $this->getId(),
            'status' => $this->getStatus(),
            'input' => $this->input,
            'output' => $this->output,
            'retries' => $this->retries,
            'queued_at' => $this->getQueuedAt() ?? $this->getQueuedAt()->format('c'),
            'started_at' => $this->getStartedAt() ?? $this->getStartedAt()->format('c'),
            'retry_at' => $this->getRetryAt() ?? $this->getRetryAt()->format('c'),
            'finished_at' => $this->getFinishedAt() ?? $this->getFinishedAt()->format('c')
        ];
    }
}
