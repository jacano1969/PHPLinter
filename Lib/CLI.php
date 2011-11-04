<?php
/**
----------------------------------------------------------------------+
*  @desc			CLI version.
----------------------------------------------------------------------+
*  @file 			Lint_class.php
*  @author 			Jóhann T. Maríusson <jtm@hi.is>
*  @since 		    Oct 29, 2011
*  @package 		PHPLinter
*  @copyright     
*    phplinter is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with this program.  If not, see <http://www.gnu.org/licenses/>.
----------------------------------------------------------------------+
*/
namespace PHPLinter; 
class CLI {
	/* @var Array */
	protected $options;
	/* @var String */
	protected $output_dir;
	/* @var String */
	protected $target;
	/* @var Array */
	protected $conf;
	/* @var Array */
	protected $use_rules;
	/* @var Array */
	protected $extensions;
	/* @var Array */
	protected $ignore;
	/* @var float */
	protected $penalty;
	/**
	----------------------------------------------------------------------+
	* @desc 	__construct. Defaults to use color.
	----------------------------------------------------------------------+
	*/
	public function __construct() {
		$this->options |= OPT_USE_COLOR;
		$this->extensions = 'php';
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Output help.
	----------------------------------------------------------------------+
	*/
	protected function help() {
		echo "PHPLinter. Lint and score PHP files.\n";
		echo "Usage phplinter -[ICWRESHvVTci[PATTERN]] [file|directory]\n";
		echo "\t-U: Use conf-file FILE.\n";
		echo "\t-I: Report extra information (default off).\n";
		echo "\t-C: Dont report conventions.\n";
		echo "\t-W: Dont report warnings.\n";
		echo "\t-R: Dont report refactor warnings.\n";
		echo "\t-E: Dont report errors.\n";
		echo "\t-D: Dont report deprecated warnings.\n";
		echo "\t-X: Dont report security warnings.\n";
		echo "\t-O: Security report only.\n";
//		echo "\t-F: Try to locate unused functions/methods (Experimental).\n";
//		echo "\t-t: Number of invocations threshold (default 0).\n";
		echo "\t-S: Score only.\n";
		echo "\t-r: Use following rules (| delimited).\n";
		echo "\t-v: Verbose mode\n";
		echo "\t-V: Debug mode. Again for extra debug info.\n";
		echo "\t-T: Time execution. Again for extra time info.\n";
		echo "\t-c: Turn off color output.\n";
		echo "\t-e: Add extensions to valid list delimited by '|' (default 'php')\n";
		echo "\t-o: Output directory (with -H)\n";
		echo "\t-H: HTML report.\n";
		echo "\t-w: Overwrite output directory.\n";
		echo "\t-i: ignore PATTERN. (Ignore files in directory mode)\n";
		echo "<jtm@robot.is>\n";
		exit;
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Process commandline options
	* @param	Array
	* @param	Int
	----------------------------------------------------------------------+
	*/
	public function process_options($argv, $argc) {
		for($i = 1;$i < $argc; $i++) {
			if($argv[$i][0] == '-') {
				$l = mb_strlen($argv[$i]);
				for($j=1; $j < $l; $j++) {
					switch($argv[$i][$j]) {
						case 'I':
							$this->options |= OPT_INFORMATION;
							break;
						case 'D':
							$this->options |= OPT_NO_DEPRICATED;
							break;
						case 'C':
							$this->options |= OPT_NO_CONVENTION;
							break;
						case 'W':
							$this->options |= OPT_NO_WARNING;
							break;
						case 'R':
							$this->options |= OPT_NO_REFACTOR;
							break;
						case 'E':
							$this->options |= OPT_NO_ERROR;
							break;
						case 'X':
							$this->options |= OPT_NO_SECURITY;
							break;
						case 'O':
							$this->options |= OPT_ONLY_SECURITY;
							break;
						case 'v':
							$this->options |= OPT_VERBOSE;
							break;
						case 'V':
							if($this->options & OPT_DEBUG) {
								$this->options |= OPT_DEBUG_EXTRA;
							} else {
								$this->options |= OPT_DEBUG;
							}
							break;
						case 'T':
							if($this->options & OPT_DEBUG_TIME) {
								$this->options |= (OPT_DEBUG_TIME_EXTRA | OPT_VERBOSE);
							}
							else $this->options |= OPT_DEBUG_TIME;
							break;
						case 'S':
							$this->options |= OPT_SCORE_ONLY;
							break;
						case 'c':
							$this->options &= ~OPT_USE_COLOR;
							break;
						case 'H':
							$this->options |= OPT_HTML_REPORT;
							break;
//						case 'F':
//							$this->options |= OPT_FIND_FUNC;
//							break;
						case 'w':
							$this->options |= OPT_OVERWRITE_REPORT;
							break;
						case 'r':
							$this->use_rules = $argv[++$i];
							continue 3;
						case 'i':
							$this->ignore = "/{$argv[++$i]}/";
							continue 3;
//						case 't':
//							$this->threshold = intval($argv[++$i]);
//							continue 3;
						case 'o':
							$this->output_dir = $argv[++$i];
							continue 3;
						case 'U':
							$this->settings_file = $argv[++$i];
							continue 3;
						case 'e':
							$ext = trim($argv[++$i]);
							if(preg_match('/[a-z0-9\|]+/iu', $ext)) {
								$this->extensions .= '|'.$ext;
							} elseif(!empty($ext)) {
								$this->error('Extensions must include only letters and numbers');
							}
							continue 3;
						case 'h':
							$this->help();
						default:
							$this->error("Unrecognized option `{$argv[$i][$j]}`");
					}
				}
			} else {
				$this->target = $argv[$i];
			}
		}
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Die with error message. Outputs help.
	* @param	String
	----------------------------------------------------------------------+
	*/
	protected function error($msg) {
		echo "$msg\n\n";
		$this->help();
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Analyse directory
	----------------------------------------------------------------------+
	*/
	protected function lint_directory() {
		$this->options |= OPT_SCORE_ONLY;
		if($this->options & OPT_HTML_REPORT) {
			if(isset($this->output_dir) 
				&& file_exists($this->output_dir) 
				&& !($this->options & OPT_OVERWRITE_REPORT)) 
			{
				$this->error('Output directory not empty, will not overwrite...');
			}
		}
		$files = (isset($this->ignore))
			? Path::find($this->target, "/^.*?\.$this->extensions$/", $this->ignore)
			: Path::find($this->target, "/^.*?\.$this->extensions$/");
			
		$verbose = ($this->options & OPT_VERBOSE);	
		$this->penalty = 0;
		$numfiles = count($files);
		foreach($files as $_) {
			if($verbose) echo "Linting file: $_\n";
			$linter = new PHPLinter($_, $this->options, $this->conf, $this->use_rules);
			$report = $linter->lint();
			$penalty = $linter->penalty();
			if($this->options & OPT_HTML_REPORT) {
				$href = (preg_match('/^\.\/?/', $_)) 
							? $_ : "./$_";
				$reports[$href] = $report;
				$penaltys[$href] = $penalty;
			}
			$this->penalty += $penalty;
			if($verbose) $this->reporter->score($penalty);
		}
		$this->reporter->average($this->penalty, $numfiles);
		if($this->options & OPT_HTML_REPORT) {
			$this->reporter->toHtml($this->target, $reports, $penaltys);
		}
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Analyse single file
	* @param	String	Filename
	----------------------------------------------------------------------+
	*/
	protected function lint_file($file) {
		$linter = new PHPLinter($file, $this->options, $this->conf, $this->use_rules);
		$report = $linter->lint();
		if(!($this->options & OPT_SCORE_ONLY)) {
			$this->reporter->toCli($report);
		}
		$this->reporter->score($linter->penalty());
	}
	/**
	----------------------------------------------------------------------+
	* @desc 	Lint target
	----------------------------------------------------------------------+
	*/
	public function lint() {
		if(!isset($this->target) || !file_exists($this->target)) {
			$this->error('Need valid target...');
		}
		$this->reporter = new Report($this->output_dir, $this->options);
		if($this->options & OPT_DEBUG_TIME) 
			$time = microtime(true);
		
		if(is_dir($this->target)) {
			$this->lint_directory();
		} else {
			$this->lint_file($this->target);
		}
		
		if($this->options & OPT_DEBUG_TIME) {
			$x = microtime(true) - $time;
			echo "Total time: $x\n";	
		}
	}
}