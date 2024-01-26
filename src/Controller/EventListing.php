<?php

namespace Drupal\event_directory\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a block for displaying event listings.
 *
 * @Block(
 *   id = "event_directory_block",
 *   admin_label = @Translation("Event Directory Block"),
 *   category = @Translation("Custom")
 * )
 */
class EventListing extends ControllerBase {

  /**
   *
   */
  public function view() {

    $apiUrl = ''; //  your api here
    $username = ''; // your username here
    $password = ''; // your password here

    $cursor = NULL;
    $events = [];

    do {
      $queryParams = [
        'cursor' => $cursor,
        'sort' => 'date',
        'order' => 'asc',
      ];

      // get response from api 
      $response = \Drupal::httpClient()->get($apiUrl, [
        'auth' => [$username, $password],
        'headers' => [
          'Content-Type' => 'application/json',
        ],
        'query' => $queryParams,
      ]);

      $data = json_decode($response->getBody(), TRUE); // Decode the json data 

      if (isset($data['data']) && count($data['data']) > 0) { //fetch the data from json 
        foreach ($data['data'] as $event) {
          $id = $event['id'];
          $name = $event['attributes']['title'];
          $date_start = $event['attributes']['start_at'];
          $date_end = $event['attributes']['end_at'];
          $description = $event['attributes']['details'];
          $images = $event['attributes']['event_image']['image_url'] ?? NULL;
          $locationurl = $event['relationships']['location']['links']['related'];

          // Fetch location data from location URL.
          $locationResponse = \Drupal::httpClient()->get($locationurl, [
            'auth' => [$username, $password],
            'headers' => [
              'Content-Type' => 'application/json',
            ],
          ]);

          $locationData = json_decode($locationResponse->getBody(), TRUE);

          $locationAddress = $locationData['data']['attributes']['address_text'] ?? '';
          $dateTime = new \DateTime($date_start);
          $dateTime = new \DateTime($date_start);
          $formattedDate_start_month = $dateTime->format('M');
          $formattedDate_start_date = $dateTime->format('j');
          $formattedDate_start_time = $dateTime->format('H:i');

          $dateTime_end = new \DateTime($date_end);
          $formattedTime_end = $dateTime_end->format('H:i');

          // Create array of events.
          $events[] = [
            'id' => $id,
            'name' => $name,
            'date_start_month' => $formattedDate_start_month,
            'date_start_date' => $formattedDate_start_date,
            'time_start' => $formattedDate_start_time,
            'time_end' => $formattedTime_end,
            'date_end' => $formattedTime_end,
            'description' => $description,
            'location_address' => $locationAddress,
            'image' => $images,
          ];
        }

        $cursor = $data['meta']['next_cursor'] ?? NULL;
      }

      usleep(500000);

    } while ($cursor !== NULL);

    return [
      '#theme' => 'event-listing',
      '#items' => $events,
    ];

  }

  /**
   *
   */
  public function build(Request $request) {
    // $tags = $request->attributes->get('tag');
    $paramValue = $request->query->get('param1');
    if ($paramValue) {
      $apiUrl = '' . $paramValue; // fetch the data form the api from the parameter value

      $username = ''; // username here for that  api 
      $password = ''; // password for that api 
      $events = []; // storage of value 

      // get response
      $response = \Drupal::httpClient()->get($apiUrl, [
        'auth' => [$username, $password],
        'headers' => [
          'Content-Type' => 'application/json',
        ],
      ]);

      // decode the json data
      $data = json_decode($response->getBody(), TRUE);

      if (isset($data['data']) && count($data['data']) > 0) {
        foreach ($data['data'] as $event) {
          $id = $event['id'];
          $name = $event['attributes']['title'];
          $date_start = $event['attributes']['start_at'];
          $date_end = $event['attributes']['end_at'];
          $description = $event['attributes']['details'];
          $tag = $event['attributes']['tags'][0];
          $images = $event['attributes']['event_image']['image_url'] ?? NULL;
          $locationurl = $event['relationships']['location']['links']['related'];

          // Fetch location data from location URL.
          $locationResponse = \Drupal::httpClient()->get($locationurl, [
            'auth' => [$username, $password],
            'headers' => [
              'Content-Type' => 'application/json',
            ],
          ]);

          $locationData = json_decode($locationResponse->getBody(), TRUE);

          $locationAddress = $locationData['data']['attributes']['address_text'] ?? '';

          $ticketurl = $event['relationships']['tickets']['links']['related'];

          $ticketResponse = \Drupal::httpClient()->get($ticketurl, [
            'auth' => [$username, $password],
            'headers' => [
              'Content-Type' => 'application/json',
            ],
          ]);

          $ticketapi = json_decode($ticketResponse->getBody(), TRUE);
          $ticketPrice = $ticketapi['data'][0]['attributes']['cost']['net'] / 100;
          $dateTime = new \DateTime($date_start);
          $formattedDate_start_month = $dateTime->format('M');
          $formattedDate_start_date = $dateTime->format('j');

          $formattedDate_start_time = $dateTime->format('H:i');

          $dateTime_end = new \DateTime($date_end);
          $formattedTime_end = $dateTime_end->format('H:i');

          $events[] = [
            'id' => $id,
            'name' => $name,
            'date_start_month' => $formattedDate_start_month,
            'date_start_date' => $formattedDate_start_date,
            'time_start' => $formattedDate_start_time,
            'time_end' => $formattedTime_end,
            'date_end' => $formattedTime_end,
            'description' => $description,
            'location_address' => $locationAddress,
            'image' => $images,
            'tag' => $tag,
            'ticket' => $ticketPrice,
          ];
        }
      }
      else {
        \Drupal::messenger()->addMessage(t('There are no events at the moment'), 'warning');
      }
      usleep(500000);

      return [
        '#theme' => 'event-details',
        '#items' => $events,
        '#cache' => [
          'contexts' => ['url.query_args'],
          'tags' => ['event_details:' . $paramValue],
        ],
      ];

    }
  }

}
