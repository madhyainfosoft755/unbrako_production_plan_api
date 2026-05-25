<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApiTimeRestriction implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // filter working check log message and die statement
        // log_message('error', 'ApiTimeRestriction filter executed'. env('apiTimeRestriction.enabled'));
        // die('FILTER WORKING');

        // Set IST timezone
        date_default_timezone_set('Asia/Kolkata');

        /*
        |--------------------------------------------------------------------------
        | MASTER ENABLE SWITCH
        |--------------------------------------------------------------------------
        */

        $enabled = env('apiTimeRestriction.enabled');

        if (!$enabled) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | DYNAMIC OR STATIC MODE
        |--------------------------------------------------------------------------
        */

        $dynamicWindow = env('apiTimeRestriction.dynamicWindow');

        if ($dynamicWindow == 'true') {

            /*
            |--------------------------------------------------------------------------
            | RANDOM WINDOW MODE
            |--------------------------------------------------------------------------
            */

            $gapHours = (int) env('apiTimeRestriction.windowGapHours', 4);

            /*
            |--------------------------------------------------------------------------
            | STORE DAILY RANDOM WINDOW
            |--------------------------------------------------------------------------
            */

            $cache = cache();

            $todayKey = 'api_time_window_' . date('Y-m-d');

            $window = $cache->get($todayKey);

            if (!$window) {

                $window = generateRandomTimeWindow($gapHours);

                // Save till end of day
                $secondsUntilMidnight = strtotime('tomorrow') - time();

                $cache->save($todayKey, $window, $secondsUntilMidnight);

                // log_message(
                //     'error',
                //     'Generated API Time Window: ' .
                //     $window['start'] . ' -> ' . $window['end']
                // );
            }

            $startTime = $window['start'];
            $endTime   = $window['end'];

        } else {

            /*
            |--------------------------------------------------------------------------
            | STATIC MODE
            |--------------------------------------------------------------------------
            */

            $startTime = env('apiTimeRestriction.staticStartTime', '10:00');
            $endTime   = env('apiTimeRestriction.staticEndTime', '14:00');
        }

        /*
        |--------------------------------------------------------------------------
        | CURRENT TIME CHECK
        |--------------------------------------------------------------------------
        */

        $currentTime = date('H:i');

        if ($currentTime < $startTime || $currentTime > $endTime) {

            // log_message(
            //     'error',
            //     'Blocked API access. Current: ' .
            //     $currentTime .
            //     ' Allowed: ' .
            //     $startTime .
            //     ' -> ' .
            //     $endTime
            // );

            return response()
                ->setStatusCode(500)
                ->setJSON([
                    'status' => false,
                    'message' => 'Internal Server Error'
                ]);
        }
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
