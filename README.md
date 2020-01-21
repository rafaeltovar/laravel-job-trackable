# Trackables Laravel Jobs

Track Laravel Jobs, saving status, inputs, outputs and date times during a week in Redis.

Based on [imTigger/laravel-job-status](https://github.com/imTigger/laravel-job-status).

## Installation

### Composer

```
composer require rafaeltovar/laravel-job-trackable
```

## Features

- [x] Job status
- [x] Date time of queue, execute, update and finished
- [x] Number of retries
- [x] Redis persist / NoSQL database
- [x] Controller for manage Tracks
- [x] Simple and light
- [ ] Cli commands

## Instructions

1. Include Service Provider into `providers` section in `config/app.php`.

```php
'providers' => [
  //...
  LaravelJobTrackable\Providers\LaravelJobTrackableServiceProvider::class,
];
```

2. Add trait to our Laravel `Job` and init track with `track` method.

```php
<?php
namespace App\Jobs;

use LaravelJobTrackable\Jobs\TrackableJob;

class TrackedJob extends Job
{
    use TrackableJob;

    public function __construct($input1, $input2) {
        // track the job-execution
        $this->track(['input1' => $input1, 'input2' => $input2]); // inputs are optionals
    }

    public function handle()
    {
        // Do something...
        $this->setOutput(['output1' => $output1]); // optional
    }
}
```
