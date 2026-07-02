#!/bin/sh
# Coolify UI env vars override docker-compose — force-disable here before automations run.
export AUTORUN_LARAVEL_MIGRATION=false

exit 0
