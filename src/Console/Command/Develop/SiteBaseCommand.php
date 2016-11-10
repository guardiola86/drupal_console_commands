<?php

/**
 * @file
 * Contains \VM\Console\Develop\SiteBaseCommand.
 */

namespace VM\Console\Command\Develop;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Config;
use VM\Console\Command\Exception\SiteCommandException;

/**
 * Class SiteBaseCommand
 *
 * @package VM\Console\Command\Develop
 */
class SiteBaseCommand extends Command {
  use CommandTrait;

  /**
   * IO interface.
   *
   * @var null
   */
  protected $io = NULL;

  /**
   * Global location for sites.yml.
   *
   * @var array
   */
  protected $configFile = NULL;

  /**
   * Stores the contents of sites.yml.
   *
   * @var array
   */
  protected $config = NULL;

  /**
   * Stores the site name.
   *
   * @var string
   */
  protected $siteName = NULL;

  /**
   * Stores the destination directory.
   *
   * @var string
   */
  protected $destination = NULL;

  /**
   * {@inheritdoc}
   */
  protected function configure() {

  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $this->validateSiteParams($input, $output);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
//    $siteConfig = $this->config['sites'][$this->siteName];
//    $repo = $siteConfig['repo'];
//    $branch = $input->getOption('branch');
//
//    $destination = $input->getOption('destination-directory');
//    // Make sure we have a slash at the end.
//    if (substr($destination, -1) != '/') {
//      $destination .= '/';
//    }

  }

  /**
   * Helper to validate parameters.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   */
  protected function validateSiteParams(InputInterface $input, OutputInterface $output) {
    $this->io = new DrupalStyle($input, $output);

    // Get config.
    $this->_getConfigFile();

    // Validate site name.
    $this->_validateSiteName($input);

    // Validate destination.
    $this->_validateDestination($input);
  }

  /**
   * Helper to check that the config file exits.
   *
   * @param $config
   * @param $configFile
   *
   * @return $config The configuration from the yml file
   *
   * @throws SiteCommandException
   */
  protected function _getConfigFile() {
    $ymlFile = new Parser();
    $config = new Config($ymlFile);
    $configFile = $config->getUserHomeDir() . '/.console/sites.yml';

    if (!file_exists($configFile)) {
      $message = sprintf('Could not find any configuration in %s', $configFile);
      throw new SiteCommandException($message);
    }
    $this->configFile = $configFile;
    $this->config = $config->getFileContents($configFile);

    return $this;
  }

  /**
   * Helper to validate site-name parameter.
   *
   * @param InputInterface $input
   *
   * @throws SiteCommandException
   */
  protected function _validateSiteName(InputInterface $input) {
    $this->siteName = $input->getArgument('site-name');
    if (!isset($this->config['sites'][$this->siteName])) {
      $message = sprintf(
        'Site not found in /.console/sites.yml' . PHP_EOL .
        'Usage: drupal site:checkout site-name' . PHP_EOL .
        'Available sites: [%s]',
        implode(', ', array_keys($this->config['sites']))
      );
      throw new SiteCommandException($message);
    };

    return $this;
  }

  /**
   * Helper to validate destination parameter.
   *
   * @param InputInterface $input
   *
   * @throws SiteCommandException
   */
  protected function _validateDestination(InputInterface $input) {
    if (!is_null($input->getOption('destination-directory'))) {
      // Use config from parameter.
      $this->destination = $input->getOption('destination-directory');
    }
    elseif (isset($this->config['global']['destination-directory'])) {
      // Use config from sites.yml.
      $this->destination = $this->config['global']['destination-directory'] .
        '/' . $this->siteName;
    }
    else {
      $this->destination = '/tmp/' . $this->siteName;
    }
    // Make sure we have a slash at the end.
    if (substr($this->destination, -1) != '/') {
      $this->destination .= '/';
    }
    // Append site name.
    if (strpos($this->destination, $this->siteName, 0) === FALSE) {
      $this->destination .= $this->siteName . '/';
    }
    return $this;
  }

}