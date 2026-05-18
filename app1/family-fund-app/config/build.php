<?php

return [
    /*
     | Preview-build identity, injected by app1/launch_docker.sh into the
     | familyfund container env (see docker-compose.env.yml). Used only to
     | render the dev branch/build banner; empty on a plain prod build.
     */
    'nickname' => env('FF_NICKNAME'),
    'ref' => env('FF_BUILD_REF'),
    'label' => env('FF_BUILD_LABEL'),
];
