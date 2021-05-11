<?php

  class job_error_reporter {
    public $id = __CLASS__;
    public $name = 'Error Reporter';
    public $description = '';
    public $author = 'LiteCart Dev Team';
    public $version = '1.0';
    public $website = 'http://www.litecart.net';
    public $priority = 0;

    public function process($force, $last_run) {

      if (empty($this->settings['status'])) return;

      if (empty($this->settings['email_recipient'])) $this->settings['email_recipient'] = settings::get('store_email');

      if (empty($force)) {
        if (!empty($this->settings['working_hours'])) {
          list($from_time, $to_time) = explode('-', $this->settings['working_hours']);
          if (time() < strtotime("Today $from_time") || time() > strtotime("Today $to_time")) return;
        }

        switch ($this->settings['report_frequency']) {
          case 'Hourly':
            if (date('Ymdh', strtotime($last_run)) == date('Ymdh')) return;
            break;
          case 'Daily':
            if (date('Ymd', strtotime($last_run)) == date('Ymd')) return;
            break;
          case 'Weekly':
            if (date('W', strtotime($last_run)) == date('W')) return;
            break;
          case 'Monthly':
            if (date('Ym', strtotime($last_run)) == date('Ym')) return;
            break;
        }
      }

      $error_log_file = ini_get('error_log');

      if (!$contents = file_get_contents($error_log_file)) return;

      echo 'Sending report to '. $this->settings['email_recipient'];

      $email = new ent_email();
      $email->add_recipient($this->settings['email_recipient'])
            ->set_subject('[Error Report] '. settings::get('store_name'))
            ->add_body(PLATFORM_NAME .' '. PLATFORM_VERSION ."\r\n\r\n". $contents);

      if ($email->send() !== true) {
        echo ' [Failed]';
        return;
      }

      file_put_contents($error_log_file, '');
    }

    function settings() {

      return array(
        array(
          'key' => 'status',
          'default_value' => '1',
          'title' => language::translate(__CLASS__.':title_status', 'Status'),
          'description' => language::translate(__CLASS__.':description_status', 'Enables or disables the module.'),
          'function' => 'toggle("e/d")',
        ),
        array(
          'key' => 'working_hours',
          'default_value' => '07:00-21:00',
          'title' => language::translate(__CLASS__.':title_working_hours', 'Working Hours'),
          'description' => language::translate(__CLASS__.':description_working_hours', 'During what hours of the day the job would operate e.g. 07:00-21:00.'),
          'function' => 'text()',
        ),
        array(
          'key' => 'report_frequency',
          'default_value' => 'Weekly',
          'title' => language::translate(__CLASS__.':title_report_frequency', 'Report Frequency'),
          'description' => language::translate(__CLASS__.':description_report_frequency', 'How often the reports should be sent.'),
          'function' => 'radio("Immediately","Hourly","Daily","Weekly","Monthly")',
        ),
        array(
          'key' => 'email_recipient',
          'default_value' => settings::get('store_email'),
          'title' => language::translate(__CLASS__.':title_email_recipient', 'Email Recipient'),
          'description' => language::translate(__CLASS__.':description_email_recipient', 'The email address where reports will be sent.'),
          'function' => 'text()',
        ),
        array(
          'key' => 'priority',
          'default_value' => '0',
          'title' => language::translate(__CLASS__.':title_priority', 'Priority'),
          'description' => language::translate(__CLASS__.':description_priority', 'Process this module in the given priority order.'),
          'function' => 'number()',
        ),
      );
    }
  }