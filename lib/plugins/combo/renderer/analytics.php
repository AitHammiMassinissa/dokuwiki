<?php


use ComboStrap\AnalyticsDocument;
use ComboStrap\BacklinkCount;
use ComboStrap\Canonical;
use ComboStrap\MarkupRef;
use ComboStrap\MetadataDbStore;
use ComboStrap\Page;
use ComboStrap\PageTitle;
use ComboStrap\PluginUtility;
use ComboStrap\StringUtility;
use dokuwiki\ChangeLog\PageChangeLog;


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


/**
 * A analysis Renderer that exports stats/quality/metadata in a json format
 * You can export the data with
 * doku.php?id=somepage&do=export_combo_analytics
 */
class renderer_plugin_combo_analytics extends Doku_Renderer
{

    const PLAINTEXT = 'formatted';
    const RESULT = "result";
    const DESCRIPTION = "description";
    const PASSED = "Passed";
    const FAILED = "Failed";
    const FIXME = 'fixme';

    /**
     * Rules key
     */
    const RULE_WORDS_MINIMAL = 'words_min';
    const RULE_OUTLINE_STRUCTURE = "outline_structure";
    const RULE_INTERNAL_BACKLINKS_MIN = 'internal_backlinks_min';
    const RULE_WORDS_MAXIMAL = "words_max";
    const RULE_AVERAGE_WORDS_BY_SECTION_MIN = 'words_by_section_avg_min';
    const RULE_AVERAGE_WORDS_BY_SECTION_MAX = 'words_by_section_avg_max';
    const RULE_INTERNAL_LINKS_MIN = 'internal_links_min';
    const RULE_INTERNAL_BROKEN_LINKS_MAX = 'internal_links_broken_max';
    const RULE_DESCRIPTION_PRESENT = 'description_present';
    const RULE_FIXME = "fixme_min";
    const RULE_TITLE_PRESENT = "title_present";
    const RULE_CANONICAL_PRESENT = "canonical_present";
    const QUALITY_RULES = [
        self::RULE_CANONICAL_PRESENT,
        self::RULE_DESCRIPTION_PRESENT,
        self::RULE_FIXME,
        self::RULE_INTERNAL_BACKLINKS_MIN,
        self::RULE_INTERNAL_BROKEN_LINKS_MAX,
        self::RULE_INTERNAL_LINKS_MIN,
        self::RULE_OUTLINE_STRUCTURE,
        self::RULE_TITLE_PRESENT,
        self::RULE_WORDS_MINIMAL,
        self::RULE_WORDS_MAXIMAL,
        self::RULE_AVERAGE_WORDS_BY_SECTION_MIN,
        self::RULE_AVERAGE_WORDS_BY_SECTION_MAX
    ];

    /**
     * The default man
     */
    const CONF_MANDATORY_QUALITY_RULES_DEFAULT_VALUE = [
        self::RULE_WORDS_MINIMAL,
        self::RULE_INTERNAL_BACKLINKS_MIN,
        self::RULE_INTERNAL_LINKS_MIN
    ];
    const CONF_MANDATORY_QUALITY_RULES = "mandatoryQualityRules";

    /**
     * Quality Score factors
     * They are used to calculate the score
     */
    const CONF_QUALITY_SCORE_INTERNAL_BACKLINK_FACTOR = 'qualityScoreInternalBacklinksFactor';
    const CONF_QUALITY_SCORE_INTERNAL_LINK_FACTOR = 'qualityScoreInternalLinksFactor';
    const CONF_QUALITY_SCORE_TITLE_PRESENT = 'qualityScoreTitlePresent';
    const CONF_QUALITY_SCORE_CORRECT_HEADER_STRUCTURE = 'qualityScoreCorrectOutline';
    const CONF_QUALITY_SCORE_CORRECT_CONTENT = 'qualityScoreCorrectContentLength';
    const CONF_QUALITY_SCORE_NO_FIXME = 'qualityScoreNoFixMe';
    const CONF_QUALITY_SCORE_CORRECT_WORD_SECTION_AVERAGE = 'qualityScoreCorrectWordSectionAvg';
    const CONF_QUALITY_SCORE_INTERNAL_LINK_BROKEN_FACTOR = 'qualityScoreNoBrokenLinks';
    const CONF_QUALITY_SCORE_CHANGES_FACTOR = 'qualityScoreChangesFactor';
    const CONF_QUALITY_SCORE_DESCRIPTION_PRESENT = 'qualityScoreDescriptionPresent';
    const CONF_QUALITY_SCORE_CANONICAL_PRESENT = 'qualityScoreCanonicalPresent';
    const SCORING = "scoring";
    const SCORE = "score";
    const HEADER_STRUCT = 'header_struct';
    const RENDERER_NAME_MODE = "combo_" . renderer_plugin_combo_analytics::RENDERER_FORMAT;

    /**
     * The format returned by the renderer
     */
    const RENDERER_FORMAT = "analytics";


    /**
     * The processing data
     * that should be {@link  renderer_plugin_combo_analysis::reset()}
     */
    public $stats = array(); // the stats
    protected $metadata = array(); // the metadata in frontmatter
    protected $headerId = 0; // the id of the header on the page (first, second, ...)

    /**
     * Don't known this variable ?
     */
    protected $quotelevel = 0;
    protected $formattingBracket = 0;
    protected $tableopen = false;
    private $plainTextId = 0;
    /**
     * @var Page
     */
    private $page;

    /**
     * Get and unset a value from an array
     * @param array $array
     * @param $key
     * @param $default
     * @return mixed
     */
    private static function getAndUnset(array &$array, $key, $default)
    {
        if (isset($array[$key])) {
            $value = $array[$key];
            unset($array[$key]);
            return $value;
        }
        return $default;

    }

    public function document_start()
    {
        $this->reset();
        $this->page = Page::createPageFromGlobalDokuwikiId();

    }


    /**
     * Here the score is calculated
     * @throws \ComboStrap\ExceptionCombo
     */
    public function document_end() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        /**
         * The exported object
         */
        $statExport = $this->stats;

        /**
         * The metadata
         */
        global $ID;
        $dokuWikiMetadata = p_get_metadata($ID);

        /**
         * Edit author stats
         */
        $changelog = new PageChangeLog($ID);
        $revs = $changelog->getRevisions(0, 10000);
        array_push($revs, $dokuWikiMetadata['last_change']['date']);
        $statExport[AnalyticsDocument::EDITS_COUNT] = count($revs);
        foreach ($revs as $rev) {


            /**
             * Init the authors array
             */
            if (!array_key_exists('authors', $statExport)) {
                $statExport['authors'] = [];
            }
            /**
             * Analytics by users
             */
            $info = $changelog->getRevisionInfo($rev);
            if (is_array($info)) {
                $user = "*";
                if (array_key_exists('user', $info)) {
                    $user = $info['user'];
                }
                if (!array_key_exists('authors', $statExport['authors'])) {
                    $statExport['authors'][$user] = 0;
                }
                $statExport['authors'][$user] += 1;
            }
        }

        /**
         * Word and chars count
         * The word count does not take into account
         * words with non-words characters such as < =
         * Therefore the node and attribute are not taken in the count
         */
        $text = rawWiki($ID);
        $statExport[AnalyticsDocument::CHAR_COUNT] = strlen($text);
        $statExport[AnalyticsDocument::WORD_COUNT] = StringUtility::getWordCount($text);


        /**
         * Internal link distance summary calculation
         */
        if (array_key_exists(AnalyticsDocument::INTERNAL_LINK_DISTANCE, $statExport)) {
            $linkLengths = $statExport[AnalyticsDocument::INTERNAL_LINK_DISTANCE];
            unset($statExport[AnalyticsDocument::INTERNAL_LINK_DISTANCE]);
            $countBacklinks = count($linkLengths);
            $statExport[AnalyticsDocument::INTERNAL_LINK_DISTANCE]['avg'] = null;
            $statExport[AnalyticsDocument::INTERNAL_LINK_DISTANCE]['max'] = null;
            $statExport[AnalyticsDocument::INTERNAL_LINK_DISTANCE]['min'] = null;
            if ($countBacklinks > 0) {
                $statExport[AnalyticsDocument::INTERNAL_LINK_DISTANCE]['avg'] = array_sum($linkLengths) / $countBacklinks;
                $statExport[AnalyticsDocument::INTERNAL_LINK_DISTANCE]['max'] = max($linkLengths);
                $statExport[AnalyticsDocument::INTERNAL_LINK_DISTANCE]['min'] = min($linkLengths);
            }
        }

        /**
         * Quality Report / Rules
         */
        // The array that hold the results of the quality rules
        $ruleResults = array();
        // The array that hold the quality score details
        $qualityScores = array();


        /**
         * No fixme
         */
        if (array_key_exists(self::FIXME, $this->stats)) {
            $fixmeCount = $this->stats[self::FIXME];
            $statExport[self::FIXME] = $fixmeCount == null ? 0 : $fixmeCount;
            if ($fixmeCount != 0) {
                $ruleResults[self::RULE_FIXME] = self::FAILED;
                $qualityScores['no_' . self::FIXME] = 0;
            } else {
                $ruleResults[self::RULE_FIXME] = self::PASSED;
                $qualityScores['no_' . self::FIXME] = $this->getConf(self::CONF_QUALITY_SCORE_NO_FIXME, 1);
            }
        }

        /**
         * A title should be present
         */
        $titleScore = $this->getConf(self::CONF_QUALITY_SCORE_TITLE_PRESENT, 10);
        if (empty($this->metadata[PageTitle::TITLE])) {
            $ruleResults[self::RULE_TITLE_PRESENT] = self::FAILED;
            $ruleInfo[self::RULE_TITLE_PRESENT] = "Add a title for {$titleScore} points";
            $this->metadata[PageTitle::TITLE] = $dokuWikiMetadata[PageTitle::TITLE];
            $qualityScores[self::RULE_TITLE_PRESENT] = 0;
        } else {
            $qualityScores[self::RULE_TITLE_PRESENT] = $titleScore;
            $ruleResults[self::RULE_TITLE_PRESENT] = self::PASSED;
        }

        /**
         * A description should be present
         */
        $descScore = $this->getConf(self::CONF_QUALITY_SCORE_DESCRIPTION_PRESENT, 8);
        if (empty($this->metadata[self::DESCRIPTION])) {
            $ruleResults[self::RULE_DESCRIPTION_PRESENT] = self::FAILED;
            $ruleInfo[self::RULE_DESCRIPTION_PRESENT] = "Add a description for {$descScore} points";
            $this->metadata[self::DESCRIPTION] = $dokuWikiMetadata[self::DESCRIPTION]["abstract"];
            $qualityScores[self::RULE_DESCRIPTION_PRESENT] = 0;
        } else {
            $qualityScores[self::RULE_DESCRIPTION_PRESENT] = $descScore;
            $ruleResults[self::RULE_DESCRIPTION_PRESENT] = self::PASSED;
        }

        /**
         * A canonical should be present
         */
        $canonicalScore = $this->getConf(self::CONF_QUALITY_SCORE_CANONICAL_PRESENT, 5);
        if (empty($this->metadata[Canonical::PROPERTY_NAME])) {
            global $conf;
            $root = $conf['start'];
            if ($ID !== $root) {
                $qualityScores[self::RULE_CANONICAL_PRESENT] = 0;
                $ruleResults[self::RULE_CANONICAL_PRESENT] = self::FAILED;
                // no link to the documentation because we don't want any html in the json
                $ruleInfo[self::RULE_CANONICAL_PRESENT] = "Add a canonical for {$canonicalScore} points";
            }
        } else {
            $qualityScores[self::RULE_CANONICAL_PRESENT] = $canonicalScore;
            $ruleResults[self::RULE_CANONICAL_PRESENT] = self::PASSED;
        }

        /**
         * Outline / Header structure
         */
        $treeError = 0;
        $headersCount = 0;
        if (array_key_exists(AnalyticsDocument::HEADER_POSITION, $this->stats)) {
            $headersCount = count($this->stats[AnalyticsDocument::HEADER_POSITION]);
            unset($statExport[AnalyticsDocument::HEADER_POSITION]);
            for ($i = 1; $i < $headersCount; $i++) {
                $currentHeaderLevel = $this->stats[self::HEADER_STRUCT][$i];
                $previousHeaderLevel = $this->stats[self::HEADER_STRUCT][$i - 1];
                if ($currentHeaderLevel - $previousHeaderLevel > 1) {
                    $treeError += 1;
                    $ruleInfo[self::RULE_OUTLINE_STRUCTURE] = "The " . $i . " header (h" . $currentHeaderLevel . ") has a level bigger than its precedent (" . $previousHeaderLevel . ")";
                }
            }
            unset($statExport[self::HEADER_STRUCT]);
        }
        $outlinePoints = $this->getConf(self::CONF_QUALITY_SCORE_CORRECT_HEADER_STRUCTURE, 3);
        if ($treeError > 0 || $headersCount == 0) {
            $qualityScores['correct_outline'] = 0;
            $ruleResults[self::RULE_OUTLINE_STRUCTURE] = self::FAILED;
            if ($headersCount == 0) {
                $ruleInfo[self::RULE_OUTLINE_STRUCTURE] = "Add headings to create a document outline for {$outlinePoints} points";
            }
        } else {
            $qualityScores['correct_outline'] = $outlinePoints;
            $ruleResults[self::RULE_OUTLINE_STRUCTURE] = self::PASSED;
        }


        /**
         * Document length
         */
        $minimalWordCount = 50;
        $maximalWordCount = 1500;
        $correctContentLength = true;
        $correctLengthScore = $this->getConf(self::CONF_QUALITY_SCORE_CORRECT_CONTENT, 10);
        $missingWords = $minimalWordCount - $statExport[AnalyticsDocument::WORD_COUNT];
        if ($missingWords > 0) {
            $ruleResults[self::RULE_WORDS_MINIMAL] = self::FAILED;
            $correctContentLength = false;
            $ruleInfo[self::RULE_WORDS_MINIMAL] = "Add {$missingWords} words to get {$correctLengthScore} points";
        } else {
            $ruleResults[self::RULE_WORDS_MINIMAL] = self::PASSED;
        }
        $tooMuchWords = $statExport[AnalyticsDocument::WORD_COUNT] - $maximalWordCount;
        if ($tooMuchWords > 0) {
            $ruleResults[self::RULE_WORDS_MAXIMAL] = self::FAILED;
            $ruleInfo[self::RULE_WORDS_MAXIMAL] = "Delete {$tooMuchWords} words to get {$correctLengthScore} points";
            $correctContentLength = false;
        } else {
            $ruleResults[self::RULE_WORDS_MAXIMAL] = self::PASSED;
        }
        if ($correctContentLength) {
            $qualityScores['correct_content_length'] = $correctLengthScore;
        } else {
            $qualityScores['correct_content_length'] = 0;
        }


        /**
         * Average Number of words by header section to text ratio
         */
        $headers = $this->stats[AnalyticsDocument::HEADING_COUNT];
        if ($headers != null) {
            $headerCount = array_sum($headers);
            $headerCount--; // h1 is supposed to have no words
            if ($headerCount > 0) {

                $avgWordsCountBySection = round($this->stats[AnalyticsDocument::WORD_COUNT] / $headerCount);
                $statExport['word_section_count']['avg'] = $avgWordsCountBySection;

                /**
                 * Min words by header section
                 */
                $wordsByHeaderMin = 20;
                /**
                 * Max words by header section
                 */
                $wordsByHeaderMax = 300;
                $correctAverageWordsBySection = true;
                if ($avgWordsCountBySection < $wordsByHeaderMin) {
                    $ruleResults[self::RULE_AVERAGE_WORDS_BY_SECTION_MIN] = self::FAILED;
                    $correctAverageWordsBySection = false;
                    $ruleInfo[self::RULE_AVERAGE_WORDS_BY_SECTION_MIN] = "The number of words by section is less than {$wordsByHeaderMin}";
                } else {
                    $ruleResults[self::RULE_AVERAGE_WORDS_BY_SECTION_MIN] = self::PASSED;
                }
                if ($avgWordsCountBySection > $wordsByHeaderMax) {
                    $ruleResults[self::RULE_AVERAGE_WORDS_BY_SECTION_MAX] = self::FAILED;
                    $correctAverageWordsBySection = false;
                    $ruleInfo[self::RULE_AVERAGE_WORDS_BY_SECTION_MAX] = "The number of words by section is more than {$wordsByHeaderMax}";
                } else {
                    $ruleResults[self::RULE_AVERAGE_WORDS_BY_SECTION_MAX] = self::PASSED;
                }
                if ($correctAverageWordsBySection) {
                    $qualityScores['correct_word_avg_by_section'] = $this->getConf(self::CONF_QUALITY_SCORE_CORRECT_WORD_SECTION_AVERAGE, 10);
                } else {
                    $qualityScores['correct_word_avg_by_section'] = 0;
                }

            }
        }

        /**
         * Internal Backlinks rule
         *
         * We used the database table to get the backlinks
         * because the replication is based on it
         * If the dokuwiki index is not up to date, we may got
         * inconsistency
         */
        $countBacklinks = BacklinkCount::createFromResource($this->page)
            ->setReadStore(MetadataDbStore::class)
            ->getValueOrDefault();
        $statExport[BacklinkCount::getPersistentName()] = $countBacklinks;
        $backlinkScore = $this->getConf(self::CONF_QUALITY_SCORE_INTERNAL_BACKLINK_FACTOR, 1);
        if ($countBacklinks == 0) {

            $qualityScores[BacklinkCount::getPersistentName()] = 0;
            $ruleResults[self::RULE_INTERNAL_BACKLINKS_MIN] = self::FAILED;
            $ruleInfo[self::RULE_INTERNAL_BACKLINKS_MIN] = "Add backlinks for {$backlinkScore} point each";

        } else {

            $qualityScores[BacklinkCount::getPersistentName()] = $countBacklinks * $backlinkScore;
            $ruleResults[self::RULE_INTERNAL_BACKLINKS_MIN] = self::PASSED;
        }

        /**
         * Internal links
         */
        $internalLinksCount = $this->stats[AnalyticsDocument::INTERNAL_LINK_COUNT];
        $internalLinkScore = $this->getConf(self::CONF_QUALITY_SCORE_INTERNAL_LINK_FACTOR, 1);
        if ($internalLinksCount == 0) {
            $qualityScores[AnalyticsDocument::INTERNAL_LINK_COUNT] = 0;
            $ruleResults[self::RULE_INTERNAL_LINKS_MIN] = self::FAILED;
            $ruleInfo[self::RULE_INTERNAL_LINKS_MIN] = "Add internal links for {$internalLinkScore} point each";
        } else {
            $ruleResults[self::RULE_INTERNAL_LINKS_MIN] = self::PASSED;
            $qualityScores[AnalyticsDocument::INTERNAL_LINK_COUNT] = $countBacklinks * $internalLinkScore;
        }

        /**
         * Broken Links
         */
        $brokenLinkScore = $this->getConf(self::CONF_QUALITY_SCORE_INTERNAL_LINK_BROKEN_FACTOR, 2);
        $brokenLinksCount = 0;
        if (array_key_exists(AnalyticsDocument::INTERNAL_LINK_BROKEN_COUNT, $this->stats)) {
            $brokenLinksCount = $this->stats[AnalyticsDocument::INTERNAL_LINK_BROKEN_COUNT];
        }
        if ($brokenLinksCount > 2) {
            $qualityScores['no_' . AnalyticsDocument::INTERNAL_LINK_BROKEN_COUNT] = 0;
            $ruleResults[self::RULE_INTERNAL_BROKEN_LINKS_MAX] = self::FAILED;
            $ruleInfo[self::RULE_INTERNAL_BROKEN_LINKS_MAX] = "Delete the {$brokenLinksCount} broken links and add {$brokenLinkScore} points";
        } else {
            $qualityScores['no_' . AnalyticsDocument::INTERNAL_LINK_BROKEN_COUNT] = $brokenLinkScore;
            $ruleResults[self::RULE_INTERNAL_BROKEN_LINKS_MAX] = self::PASSED;
        }

        /**
         * Media
         */
        $mediasStats = [
            "total_count" => self::getAndUnset($statExport, AnalyticsDocument::MEDIA_COUNT, 0),
            "internal_count" => self::getAndUnset($statExport, AnalyticsDocument::INTERNAL_MEDIA_COUNT, 0),
            "internal_broken_count" => self::getAndUnset($statExport, AnalyticsDocument::INTERNAL_BROKEN_MEDIA_COUNT, 0),
            "external_count" => self::getAndUnset($statExport, AnalyticsDocument::EXTERNAL_MEDIA_COUNT, 0)
        ];
        $statExport['media'] = $mediasStats;

        /**
         * Changes, the more changes the better
         */
        $qualityScores[AnalyticsDocument::EDITS_COUNT] = $statExport[AnalyticsDocument::EDITS_COUNT] * $this->getConf(self::CONF_QUALITY_SCORE_CHANGES_FACTOR, 0.25);


        /**
         * Quality Score
         */
        ksort($qualityScores);
        $qualityScoring = array();
        $qualityScoring[self::SCORE] = array_sum($qualityScores);
        $qualityScoring["scores"] = $qualityScores;


        /**
         * The rule that if broken will set the quality level to low
         */
        $brokenRules = array();
        foreach ($ruleResults as $ruleName => $ruleResult) {
            if ($ruleResult == self::FAILED) {
                $brokenRules[] = $ruleName;
            }
        }
        $ruleErrorCount = sizeof($brokenRules);
        if ($ruleErrorCount > 0) {
            $qualityResult = $ruleErrorCount . " quality rules errors";
        } else {
            $qualityResult = "All quality rules passed";
        }

        /**
         * Low level Computation
         */
        $mandatoryRules = preg_split("/,/", $this->getConf(self::CONF_MANDATORY_QUALITY_RULES));
        $mandatoryRulesBroken = [];
        foreach ($mandatoryRules as $lowLevelRule) {
            if (in_array($lowLevelRule, $brokenRules)) {
                $mandatoryRulesBroken[] = $lowLevelRule;
            }
        }
        /**
         * Low Level
         */
        $lowLevel = false;
        $brokenRulesCount = sizeof($mandatoryRulesBroken);
        if ($brokenRulesCount > 0) {
            $lowLevel = true;
            $quality["message"] = "$brokenRulesCount mandatory rules broken.";
        } else {
            $quality["message"] = "No mandatory rules broken";
        }
        if ($this->page->isSecondarySlot()) {
            $lowLevel = false;
        }
        $this->page->setLowQualityIndicatorCalculation($lowLevel);

        /**
         * Building the quality object in order
         */
        $quality[AnalyticsDocument::LOW] = $lowLevel;
        if (sizeof($mandatoryRulesBroken) > 0) {
            ksort($mandatoryRulesBroken);
            $quality[AnalyticsDocument::FAILED_MANDATORY_RULES] = $mandatoryRulesBroken;
        }
        $quality[self::SCORING] = $qualityScoring;
        $quality[AnalyticsDocument::RULES][self::RESULT] = $qualityResult;
        if (!empty($ruleInfo)) {
            $quality[AnalyticsDocument::RULES]["info"] = $ruleInfo;
        }

        ksort($ruleResults);
        $quality[AnalyticsDocument::RULES][AnalyticsDocument::DETAILS] = $ruleResults;

        /**
         * Metadata
         */
        $page = Page::createPageFromGlobalDokuwikiId();
        $meta = $page->getMetadataForRendering();
        foreach ($meta as $key => $value) {
            /**
             * The metadata may have been set
             * by frontmatter
             */
            if (!isset($this->metadata[$key])) {
                $this->metadata[$key] = $value;
            }
        }


        /**
         * Building the Top JSON in order
         */
        global $ID;
        $finalStats = array();
        $finalStats["date"] = date('Y-m-d H:i:s', time());
        ksort($this->metadata);
        $finalStats[AnalyticsDocument::METADATA] = $this->metadata;
        ksort($statExport);
        $finalStats[AnalyticsDocument::STATISTICS] = $statExport;
        $finalStats[AnalyticsDocument::QUALITY] = $quality; // Quality after the sort to get them at the end


        /**
         * The result can be seen with
         * doku.php?id=somepage&do=export_combo_analysis
         *
         * Set the header temporarily for the export.php file
         *
         * The mode in the export is
         */
        $mode = "combo_" . $this->getPluginComponent();
        p_set_metadata(
            $ID,
            array("format" => array($mode => array("Content-Type" => 'application/json'))),
            false,
            false // Persistence is needed because there is a cache
        );
        $json_encoded = json_encode($finalStats, JSON_PRETTY_PRINT);

        $this->doc .= $json_encoded;

    }

    /**
     */
    public function getFormat()
    {
        return self::RENDERER_FORMAT;
    }



    public function header($text, $level, $pos)
    {
        if (!array_key_exists(AnalyticsDocument::HEADING_COUNT, $this->stats)) {
            $this->stats[AnalyticsDocument::HEADING_COUNT] = [];
        }
        $heading = 'h' . $level;
        if (!array_key_exists(
            $heading,
            $this->stats[AnalyticsDocument::HEADING_COUNT])) {
            $this->stats[AnalyticsDocument::HEADING_COUNT][$heading] = 0;
        }
        $this->stats[AnalyticsDocument::HEADING_COUNT][$heading]++;

        $this->headerId++;
        $this->stats[AnalyticsDocument::HEADER_POSITION][$this->headerId] = $heading;

        /**
         * Store the level of each heading
         * They should only go from low to highest value
         * for a good outline
         */
        if (!array_key_exists(AnalyticsDocument::HEADING_COUNT, $this->stats)) {
            $this->stats[self::HEADER_STRUCT] = [];
        }
        $this->stats[self::HEADER_STRUCT][] = $level;

    }

    public function smiley($smiley)
    {
        if ($smiley == 'FIXME') $this->stats[self::FIXME]++;
    }

    public function linebreak()
    {
        if (!$this->tableopen) {
            $this->stats['linebreak']++;
        }
    }

    public function table_open($maxcols = null, $numrows = null, $pos = null) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->tableopen = true;
    }

    public function table_close($pos = null) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->tableopen = false;
    }

    public function hr()
    {
        $this->stats['hr']++;
    }

    public function quote_open() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->stats['quote_count']++;
        $this->quotelevel++;
        $this->stats['quote_nest'] = max($this->quotelevel, $this->stats['quote_nest']);
    }

    public function quote_close() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->quotelevel--;
    }

    public function strong_open() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->formattingBracket++;
    }

    public function strong_close() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->formattingBracket--;
    }

    public function emphasis_open() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->formattingBracket++;
    }

    public function emphasis_close() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->formattingBracket--;
    }

    public function underline_open() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->formattingBracket++;
    }

    public function addToDescription($text){

    }

    public function underline_close() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->formattingBracket--;
    }

    public function cdata($text)
    {

        /**
         * It seems that you receive cdata
         * when emphasis_open / underline_open / strong_open
         * Stats are not for them
         */
        if (!$this->formattingBracket) return;

        $this->plainTextId++;

        /**
         * Length
         */
        $len = strlen($text);
        $this->stats[self::PLAINTEXT][$this->plainTextId]['len'] = $len;


        /**
         * Multi-formatting
         */
        if ($this->formattingBracket > 1) {
            $numberOfFormats = 1 * ($this->formattingBracket - 1);
            $this->stats[self::PLAINTEXT][$this->plainTextId]['multiformat'] += $numberOfFormats;
        }

        /**
         * Total
         */
        $this->stats[self::PLAINTEXT][0] += $len;
    }

    public function internalmedia($src, $title = null, $align = null, $width = null, $height = null, $cache = null, $linking = null)
    {
        $this->stats[AnalyticsDocument::INTERNAL_MEDIA_COUNT]++;
    }

    public function externalmedia($src, $title = null, $align = null, $width = null, $height = null, $cache = null, $linking = null)
    {
        $this->stats[AnalyticsDocument::EXTERNAL_MEDIA_COUNT]++;
    }

    public function reset()
    {
        $this->stats = array();
        $this->metadata = array();
        $this->headerId = 0;
    }

    public function setAnalyticsMetaForReporting($key, $value)
    {
        $this->metadata[$key] = $value;
    }


}

