<?php
ignore_user_abort(true);
set_time_limit(0);
file_put_contents('async_test.log', "Started\n", FILE_APPEND);
sleep(3);
file_put_contents('async_test.log', "Finished\n", FILE_APPEND);
