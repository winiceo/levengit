<?php
/**
 * 
 * Factory for a log adapter.
 * 
 * {{code: php
 *     // example setup of a single adapter
 *     $config = array(
 *         'adapter' => 'Genv_Log_Adapter_File',
 *         'events'  => '*',
 *         'file'    => '/path/to/file.log',
 *     );
 *     $log = Genv::factory('Genv_Log', $config);
 *     
 *     // write/record/report/etc an event in the log.
 *     // note that we don't do "priority levels" here, just
 *     // class names and event types.
 *     $log->save('class_name', 'event_name', 'message text');
 * }}
 * 
 */
class Genv_Log extends Genv_Factory{
  
    protected $_Genv_Log = array(
        'adapter' => 'Genv_Log_Adapter_None',
    );
}
