set :stage, :staging
set :branch, :develop
set :grunt_tasks, 'build'

role :web, %w{deploy@podstats.org}
server 'podstats.org', user: 'deploy', roles: %w{web}
