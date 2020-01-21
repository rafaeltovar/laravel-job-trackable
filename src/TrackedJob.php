<?php
namespace LaravelJobTrackable;

class TrackedJob
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
        $factory = new RandomLib\Factory;
        $generator = $factory->getGenerator(new SecurityLib\Strength(SecurityLib\Strength::MEDIUM));
        $this->id = $generator->generateString(6);
        $this->type = $type;
        $this->input;
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

    public function setOutput(array $output = []) : self
    {
        $this->output = $output;

        return $this;
    }

    public function try() : self
    {
        $this->tries++;

        return $this;
    }
}
