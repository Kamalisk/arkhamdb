php app/console app:import:trans -l de ../dump/
php app/console app:import:trans -l it ../dump/
php app/console app:import:trans -l es ../dump/
php app/console app:import:trans -l fr ../dump/

php app/console translation:update en --dump-messages --force AppBundle --output-format=xlf
php app/console translation:update de --dump-messages --force AppBundle --output-format=xlf
php app/console translation:update it --dump-messages --force AppBundle --output-format=xlf
php app/console translation:update es --dump-messages --force AppBundle --output-format=xlf
php app/console translation:update fr --dump-messages --force AppBundle --output-format=xlf

