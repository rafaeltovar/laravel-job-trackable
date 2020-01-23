<?php
namespace LaravelJobTrackable;

use Carbon\Carbon,
    Carbon\CarbonInterface;

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
        $this->id = $generator->generateString(6, "0123456789abcdefghijklmnopqrstuvwxyz");
        $this->type = $type;
        $this->input = $input;
        $this->output = [];
        //$this->status = self::STATUS_QUEUED;
        //$this->queuedAt = Carbon::now();
        $this->setStatus(self::STATUS_QUEUED);
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
                if($this->tries==0) {
                    $this->startedAt = Carbon::now();
                    $this->try();
                } else {
                    $status = self::STATUS_RETRYING;
                    $this->setStatus($status);
                }
                break;
            case self::STATUS_FINISHED:
            case self::STATUS_FAILED:
                $this->finishedAt = Carbon::now();
                break;
            case self::STATUS_QUEUED:
                $this->queuedAt = Carbon::now();
                break;
            case self::STATUS_RETRYING:
                $this->retryAt = Carbon::now();
                $this->try();
                break;
            default:
                throw new \Exception(sprintf("Status '%s' not allowed.", $status));

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

    public function getQueuedAt() : ?Carbon
    {
        return $this->queuedAt;
    }

    public function getStartedAt() : ?Carbon
    {
        return $this->startedAt;
    }

    public function getRetryAt() : ?Carbon
    {
        return $this->retryAt;
    }

    public function getFinishedAt() : ?Carbon
    {
        return $this->finishedAt;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'status' => $this->getStatus(),
            'input' => $this->input,
            'output' => $this->output,
            'tries' => $this->tries,
            'queued_at' => $this->getQueuedAt(),
            'started_at' => $this->getStartedAt(),
            'retry_at' => $this->getRetryAt(),
            'finished_at' => $this->getFinishedAt()
        ];
    }
}
