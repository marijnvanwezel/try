<?php declare(strict_types=1);
/**
 * This file is part of marijnvanwezel/try.
 *
 * (c) Marijn van Wezel <marijnvanwezel@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace MarijnVanWezel\Try;

use Psy\Shell;

class Try_ {
	/**
	 * @var string The directory where the sandbox will be located
	 */
	private string $sandboxDir;

	/**
	 * @var string The location of the Composer binary
	 */
	private string $composerBin;

	public function __construct() {
		$this->sandboxDir = __DIR__ . '/../try_sandbox__' . md5((string)rand());

		// Register some cleanup functions
		pcntl_signal(SIGTERM, [$this, 'destruct']);
		pcntl_signal(SIGINT, [$this, 'destruct']);
		register_shutdown_function([$this, 'destruct']);

		$composerBin = static::findComposerBin();

		if ($composerBin === null) {
			$errorMessage = 'You need to set up your project\'s dependencies using Composer:' . PHP_EOL . PHP_EOL .
				'    composer install' . PHP_EOL . PHP_EOL .
				'You can learn more about Composer on https://getcomposer.org/.' . PHP_EOL;

			fwrite(STDERR, $errorMessage);

			die(1);
		}

		$this->composerBin = $composerBin;
	}

	/**
	 * Open a shell with the given packages installed.
	 *
	 * @param array $packages
	 * @return void
	 */
	public function try(array $packages): void {
		echo "==> Use PHP \e[1m" . PHP_VERSION . "\e[0m" . PHP_EOL;

		$this->initSandbox($packages);

		// Start the shell
		require $this->sandboxDir . '/vendor/autoload.php';
		(new Shell())->run();
	}

	/**
	 * Initialises the sandbox with the given packages.
	 *
	 * @param array $packages
	 * @return void
	 */
	private function initSandbox(array $packages): void
	{
		mkdir($this->sandboxDir);
		file_put_contents($this->sandboxDir . '/composer.json', '{}');

		$this->installPackages($packages);
	}

	/**
	 * Installs the given packages.
	 *
	 * @param array $packages
	 * @return void
	 */
	private function installPackages(array $packages): void
	{
		foreach ($packages as $package) {
			echo "[ ] Download \e[1m$package\e[0m from Composer";

			if ($this->installPackage($package)) {
				echo "\r[*] Download \e[1m$package\e[0m from Composer" . PHP_EOL;
			} else {
				echo "\n\e[0;31mERROR: failed to download \e[1m$package\e[0m" . PHP_EOL;
				exit();
			}
		}
	}

	/**
	 * Installs the given package and returns true if and only if it succeeded.
	 *
	 * @param string $package
	 * @return bool
	 */
	private function installPackage(string $package): bool
	{
		$command = $this->composerBin . ' require ' . escapeshellarg($package);
		$descriptors = [
			['pipe', 'r'],
			['pipe', 'w'],
			['pipe', 'w']
		];

		$process = proc_open($command, $descriptors, $_, $this->sandboxDir);

		if (is_resource($process)) {
			return proc_close($process) === 0;
		}

		// Opening the process failed
		return false;
	}

	/**
	 * Cleanup function called at the end of the script.
	 *
	 * @return void
	 */
	public function destruct(): void
	{
		// Simple check to make sure we are not deleting root
		if (strlen($this->sandboxDir) > 16 && file_exists($this->sandboxDir)) {
			exec('rm -r ' . escapeshellarg($this->sandboxDir));
		}

		exit(0);
	}

	/**
	 * Returns the path to the Composer binary, or NULL if the binary could not be found.
	 *
	 * @return string|null
	 */
	private static function findComposerBin(): ?string
	{
		global $_composer_bin_dir;
		$composer_bin_dir_candidates = isset($_composer_bin_dir) ? [$_composer_bin_dir] : [
			__DIR__ . '/../../../bin',
			__DIR__ . '/../../bin',
			__DIR__ . '/../../vendor/bin',
			__DIR__ . '/../vendor/bin'
		];

		foreach ($composer_bin_dir_candidates as $path) {
			if (is_readable($path)) {
				return $path . '/composer';
			}
		}

		return null;
	}
}
