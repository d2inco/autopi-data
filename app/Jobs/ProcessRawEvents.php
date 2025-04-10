<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PhpMqtt\Client\Facades\MQTT;

use Carbon\Carbon;

use App\Models\Events;
use App\Models\RawEvents;
use Exception;
use PhpParser\Node\Stmt\TryCatch;

class ProcessRawEvents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $verbose;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($verbose = false)
    {
        $this->verbose = $verbose;
    }

    private function convert_json_to_structure($jsontext)
    {             // {{{2
        global $debug_json_decode;

        // $debug_json_decode = true;

        if ($debug_json_decode) {
            printf("json input:\n%s\n-----------\n", $jsontext);
        }


        $jsontext = preg_replace(
            ",\"David's AutoPi\",",
            "'Davids AutoPi'",
            $jsontext
        );

        $jsontext = preg_replace(
            '/u"([^\'"]*?)\'([^\'"]*?)\'([^"]*?)",/',
            'u\'\1-\2-\3\',',
            $jsontext
        );

        $jsontext = preg_replace(
            '/u"([^\']*?)\'([^\']*?)",/',
            'u\'\1-\2\',',
            $jsontext
        );

        $jsontext = preg_replace(
            '/system\/\*/',
            'system/WILDCARD',
            $jsontext
        );

        $jsontext = preg_replace(
            '/"(Unable to verify.*?no supported protocol found)"/',
            '\'\1\'',
            $jsontext
        );

        $jsontext = preg_replace(
            '/of protocol \'auto\': /',
            'of protocol AUTO: ',
            $jsontext
        );

        if ($debug_json_decode) {
            printf("After first replacement:\n%s\n-----------\n", $jsontext);
        }

        $r = json_decode($jsontext, true);

        if ($debug_json_decode) {
            printf("line: %d\n", __LINE__);
            printf ("last error: '%s'\n", json_last_error());
            printf ("last error mesg: '%s'\n", json_last_error_msg());
            print "First decode:\n";
            print_r($r);
        }

        $unicodeCleanupPattern = array();
        $unicodeCleanupReplace = array();

        $unicodeCleanupPattern[] = '/({|: |, )u?\'/';
        $unicodeCleanupReplace[] = '\1"';
        $unicodeCleanupPattern[] = '/\'(: |, |}, |}$|}{2,4}(,|$))/';
        $unicodeCleanupReplace[] = '"\1';

        $unicodeCleanupPattern[] = '/(: )(True|False|None|<Device:[^>]*>)(, ")/';
        $unicodeCleanupReplace[] = '\1"\2"\3';

        $unicodeCleanupPattern[] = '/UUID\(\'[^\']*\'\)/';
        $unicodeCleanupReplace[] = '"\1"';

        if (isset($r['event'])) {
            $eventOneOffPattern = array();
            $eventOneOffReplace = array();
            // $eventOneOffPattern[] = "/u'{-reason-: ('[^'][^']*)', 'type': '('[^'][^']*)'}',/";
            $eventOneOffPattern[] =    "/u'{-reason-: '([^'][^']*)', 'type': '([^'][^']*)'}',/";
            $eventOneOffReplace[] = '"\1 (\2)",';

            $r['event'] = preg_replace($eventOneOffPattern, $eventOneOffReplace, $r['event']);

            $r['event'] = preg_replace($unicodeCleanupPattern, $unicodeCleanupReplace, $r['event']);

            $event = json_decode($r['event'], true);
            if (json_last_error() == 0) {
                ksort($event);
                $r['event'] = $event;
            } else {
                $r['event_json_error'] = "event: " . json_last_error_msg();
            }
        }

        if (isset($r['trigger'])) {
            $unicodeCleanupPattern[] = '/, [0-9][0-9]*, tzinfo=<UTC>/';
            $unicodeCleanupReplace[] = '';

            $unicodeCleanupPattern[] = '/(datetime.datetime\(202[0-9],) ([0-9], [^\)]*?\))/';
            $unicodeCleanupReplace[] = '\1 0\2';

            $unicodeCleanupPattern[] = '/(datetime.datetime\(202[0-9], [0-9][0-9],) ([0-9], [^\)]*?\))/';
            $unicodeCleanupReplace[] = '\1 0\2';

            $unicodeCleanupPattern[] = '/(datetime.datetime\(202[0-9], [0-9][0-9], [0-9][0-9],) ([0-9], [^\)]*?\))/';
            $unicodeCleanupReplace[] = '\1 0\2';
            $unicodeCleanupPattern[] = '/(datetime.datetime\(202[0-9], [0-9][0-9], [0-9][0-9], [0-9][0-9],) ([0-9], [^\)]*?\))/';
            $unicodeCleanupReplace[] = '\1 0\2';
            $unicodeCleanupPattern[] = '/(datetime.datetime\(202[0-9], [0-9][0-9], [0-9][0-9], [0-9][0-9], [0-9][0-9],) ([0-9]\))/';
            $unicodeCleanupReplace[] = '\1 0\2';
            $unicodeCleanupPattern[] = '/datetime.datetime\((202[0-9]), ([0-9][0-9]), ([0-9][0-9]), ([0-9][0-9]), ([0-9][0-9]), ([0-9][0-9])\)/';
            $unicodeCleanupReplace[] = '"\1-\2-\3T\4:\5:\6Z"';

            $r['trigger'] = preg_replace($unicodeCleanupPattern, $unicodeCleanupReplace, $r['trigger']);

            $trigger = json_decode($r['trigger'], true);
            if (json_last_error() == 0) {
                ksort($trigger);
                $r['trigger'] = $trigger;
            } else {
                $r['trigger_json_error'] = "trigger: " . json_last_error_msg();
            }
        }

        if (isset($r['device'])) {
            $r['device'] = preg_replace($unicodeCleanupPattern, $unicodeCleanupReplace, $r['device']);

            $device = json_decode($r['device'], true);
            if (json_last_error() == 0) {
                ksort($device);
                $r['device'] = $device;
            } else {
                $r['device_json_error'] = "device: " . json_last_error_msg();
            }
        }

        if (isset($r['profile'])) {
            $r['profile'] = preg_replace($unicodeCleanupPattern, $unicodeCleanupReplace, $r['profile']);

            $profile = json_decode($r['profile'], true);
            if (json_last_error() == 0) {
                ksort($profile);
                $r['profile'] = $profile;
            } else {
                $r['profile_json_error'] = "profile: " . json_last_error_msg();
            }
        }

        if (isset($r['model'])) {
            $r['model'] = preg_replace($unicodeCleanupPattern, $unicodeCleanupReplace, $r['model']);

            $model = json_decode($r['model'], true);
            if (json_last_error() == 0) {
                ksort($model);
                $r['model'] = $model;
            } else {
                $r['model_json_error'] = "model: " . json_last_error_msg();
            }
        }

        if (isset($r['make'])) {
            $r['make'] = preg_replace($unicodeCleanupPattern, $unicodeCleanupReplace, $r['make']);

            $make = json_decode($r['make'], true);
            if (json_last_error() == 0) {
                ksort($make);
                $r['make'] = $make;
            } else {
                $r['make_json_error'] = "make: " . json_last_error_msg();
            }
        }

        if ($debug_json_decode) {
            print "Last Decode:\n";
            print_r($r);
        }

        return $r;
        // }}}2
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $rawEvents = RawEvents::where('processed', 0)
            ->orderBy('created_at')
            ->get();

        foreach ($rawEvents as $r) {
            printf("Raw Row %3d: %s\n", $r->id, $r->updated_at);

            $e = $this->convert_json_to_structure($r->raw_data);

            print("Event:\n");
            if ($this->verbose) {
                print_r($e['event']);
            } else {
                printf("  %s: ", $e['event']['@rec']);
                printf("%s  ", $e['event']['@ts']);
                printf("%-20.20s  ", $e['event']['@t']);
                printf("%-25.25s  ", $e['event']['@tag']);
                printf("%s  ", $e['event']['@rec']);

                printf("\n");
            }
            try {
                printf("in:  %s\n", $e['event']['@rec']);
                printf("out: %s\n", Carbon::parse($e['event']['@rec'])->format('Y-m-d H:i:s.u'));

                $event_data_hash = $e['event'];
                unset($event_data_hash['@rec']);
                unset($event_data_hash['@t']);
                unset($event_data_hash['@tag']);
                unset($event_data_hash['@ts']);
                unset($event_data_hash['@uid']);
                unset($event_data_hash['@vid']);


                $eventRec = Events::updateOrCreate(
                    ['raw_id' => $r->id],
                    [
                        'rec' => Carbon::parse($e['event']['@rec'])->format('Y-m-d H:i:s.u'),
                        'event_type' => $e['event']['@t'],
                        'event_tag' => $e['event']['@tag'],
                        'ts'  => Carbon::parse($e['event']['@ts'])->format('Y-m-d H:i:s.u'),
                        'trigger_desc' => $e['trigger']['description'] ?? "",
                        'event_data' => $event_data_hash,
                    ]
                );

                MQTT::publish('vehicle/rebel/lasttag', $eventRec['event_tag'] ?? 'n/a', true);

                if (preg_match('/^(vehicle\/position\/(standstill|moving))|^(vehicle\/motion\/)|^(vehicle\/engine\/(not_running|running|stopped))/', $eventRec['event_tag'])) {
                    printf("It's a position report. (Event->id is %d)\n", $eventRec['id']);
                    GetStatsAtMovementChange::dispatch($eventRec['id'], $eventRec['ts']);
                } else {
                    printf("Not a position report; nothing to do. (Event->id is: %d)\n", $eventRec['id']);
                    if (0 == 1) {
                        // don't turn this on in production
                        GetStatsAtMovementChange::dispatch($eventRec['id'], $eventRec['ts']);
                    }
                }

                if (preg_match('/^(?:system\/power\/(.*))/', $eventRec['event_tag'], $matches)) {
                    MQTT::publish('vehicle/rebel/power/state', $matches[1], true);
                    MQTT::publish('vehicle/rebel/power/ts', $eventRec['ts'], true);
                } elseif (preg_match('/^(?:vehicle\/engine\/(.*))/', $eventRec['event_tag'], $matches)) {
                    MQTT::publish('vehicle/rebel/engine/state', $matches[1], true);
                    MQTT::publish('vehicle/rebel/engine/ts', $eventRec['ts'], true);
                }

                $r->processed = 1;
                $r->save();
            } catch (Exception $e) {
                printf("\n\nException: %s\n", $e->getMessage());
            }
        }

        // printf("Returning from handling the ProcessRawEvents() job.\n");
    }
}
