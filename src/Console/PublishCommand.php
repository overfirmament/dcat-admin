<?php

namespace Dcat\Admin\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class PublishCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'admin:publish
    {--force : Overwrite any existing files}
    {--lang : Publish language files}
    {--assets : Publish assets files}
    {--migrations : Publish migrations files}
    {--config : Publish configuration files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Re-publish dcat-admin's assets, configuration, language and migration files. If you want overwrite the existing files, you can add the `--force` option";

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var array
     */
    protected $tags = [];

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    public function handle()
    {
        $options = [];

        if ($this->option('force')) {
            $options['--force'] = true;
        }

        $tags = $this->getTags();

        foreach ($tags as $tag) {
            $this->call('vendor:publish', $options + ['--tag' => $tag]);
        }

        foreach ($this->tags as $tag) {
            $this->publishTag($tag);
        }

        $this->call('view:clear');
    }

    protected function getTags()
    {
        $tags = [];

        if ($this->option('lang')) {
            $this->tags[] = 'dcat-admin-lang';
        }
        if ($this->option('migrations')) {
            $tags[] = 'dcat-admin-migrations';
        }
        if ($this->option('assets')) {
            $tags[] = 'dcat-admin-assets';
        }
        if ($this->option('config')) {
            $tags[] = 'dcat-admin-config';
        }

        // 设置默认标签.
        if (! $tags && ! $this->tags) {
            $this->tags[] = 'dcat-admin-lang';
            $tags = [
                'dcat-admin-migrations',
                'dcat-admin-assets',
                'dcat-admin-config',
            ];
        }

        return $tags;
    }

    protected function publishTag($tag)
    {
        $published = false;

        foreach ($this->pathsToPublish($tag) as $from => $to) {
            $this->publishItem($from, $to);

            $published = true;
        }

        if ($published) {
            $this->info('Publishing complete.');
        } else {
            $this->error('Unable to locate publishable resources.');
        }
    }

    protected function pathsToPublish($tag)
    {
        return ServiceProvider::pathsToPublish(null, $tag);
    }

    protected function publishItem($from, $to)
    {
        if ($this->files->isFile($from)) {
            return $this->publishFile($from, $to);
        } elseif ($this->files->isDirectory($from)) {
            return $this->publishDirectory($from, $to);
        }

        $this->error("Can't locate path: <{$from}>");
    }

    protected function publishFile($from, $to)
    {
        if (! $this->files->exists($to) || $this->option('force')) {
            $this->createParentDirectory(dirname($to));

            $this->files->copy($from, $to);

            $this->status($from, $to, 'File');
        }
    }

    protected function publishDirectory($from, $to)
    {
        $this->createParentDirectory($to);

        foreach ($this->files->allFiles($from) as $file) {
            $relativePath = str_replace('\\', '/', $file->getRelativePathname());
            $target = rtrim($to, '/\\').DIRECTORY_SEPARATOR.$relativePath;

            if ($this->shouldSkipPath($target, $relativePath)) {
                continue;
            }

            $this->createParentDirectory(dirname($target));

            $this->files->copy($file->getPathname(), $target);
        }

        $this->status($from, $to, 'Directory');
    }

    protected function shouldSkipPath($target, $relativePath)
    {
        if (! $this->files->exists($target)) {
            return false;
        }

        if ($this->isExceptPath($relativePath)) {
            return true;
        }

        return ! $this->option('force');
    }

    protected function isExceptPath($path)
    {
        $segments = explode('/', $path);

        return in_array('menu.php', $segments, true) || in_array('global.php', $segments, true);
    }

    protected function createParentDirectory($directory)
    {
        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }
    }

    protected function status($from, $to, $type)
    {
        $from = str_replace(base_path(), '', realpath($from));

        $to = str_replace(base_path(), '', realpath($to));

        $this->line('<info>Copied '.$type.'</info> <comment>['.$from.']</comment> <info>To</info> <comment>['.$to.']</comment>');
    }
}
