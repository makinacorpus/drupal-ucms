# Very important notice

All data added using *hook_schema_alter()* must be dropped into additional
database tables and managed using additional services: this hook does not
existing anymore in Drupal 8 and this will be a serious problem whenever
we'll need to port.
