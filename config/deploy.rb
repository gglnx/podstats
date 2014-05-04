set :application, 'podstats'
set :repo_url, 'git@github.com:gglnx/podstats.git'
set :deploy_to, "/var/www/#{fetch(:application)}"
set :log_level, :info
set :linked_files, %w{.env}
set :linked_dirs, %w{node_modules}
set :keep_releases, 5
set :npm_flags, '--silent'
set :file_permissions_paths, ["Application/Cache"]
set :file_permissions_chmod_mode, "0774"

set :ssh_options, {
	forward_agent: true,
	port: 22015
}

namespace :podstats do
	desc "Create cache directory"

	task :cache_directory do
		on roles(:web), in: :sequence, wait: 5 do
			execute :mkdir, '-p', release_path.join("Application/Cache")
		end
	end
end

after 'deploy:updating', 'podstats:cache_directory'
before 'deploy:updated', 'grunt'
before 'deploy:updated', 'deploy:set_permissions:chmod'
