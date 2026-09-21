<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Alias the legacy jenssegers/agent namespace
    |--------------------------------------------------------------------------
    |
    | jenssegers/agent isn't a dependency of this package — it's what this
    | package replaces. Code that still does `use Jenssegers\Agent\Agent;`
    | (bypassing the facade, so it never touches the container) will fatal
    | with a class-not-found error unless that class name resolves to
    | something. Turning this on makes it resolve to this package's Agent,
    | via class_alias(), so no source changes are needed during a migration.
    |
    | Leave this off once you've swept your codebase for the old import.
    |
    */

    'alias_legacy_namespace' => env('AGENT_ALIAS_LEGACY_NAMESPACE', false),

];
