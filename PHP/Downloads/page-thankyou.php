<?php
/**
 * @package WordPress
 * @subpackage Classic_Theme
 */
/*
Template Name: Thank You Page
*/
!empty($_GET['for']) ? $for = $_GET['for'] : $for = false;
if ($for)
{
	if (isset($_COOKIE['@@@']))
	{
		$user = $_COOKIE['@@@'];
		$for == 'signup' ? $thanksType = $for : $for = $for;
	}
	else if (!isset($_COOKIE['@@@']) && $for != 'signup')
	{
		$location = isset($_GET['prod']) ? '/store/login/?return_url=%2Fsupport%2Fthank-you%3Fprod%3D'. $_GET['prod'] .'%26for%3D' . $for : '/store/login/?return_url=%2Fsupport%2Fthank-you%3Ffor%3D' . $for;
		Header('Location: ' . $location);
		exit();
	}
	else
	{
		$thanksType = $for;
	}
	isset($_COOKIE['@@@']) ? $download = ($_COOKIE['@@@']) : $download = false;

	$user = json_decode(stripslashes($_COOKIE['@@@']), true);
	$download ? $download = json_decode(stripslashes($_COOKIE['@@@']), true) : $download = $download;

	if ($download)
	{
		$thanksType = 'download';
		if ($download['singleDownload'])
		{
			$link = $download[0]['url'];
			$info = 'Your '.$download[0]['header'].' download will begin shortly.<br> If no download starts please click <a href="' . $link . '" id = "download">here</a>.';
			$message = 'Enjoy!';
			$downloadFrame = '<iframe width="1px" height="1px" frameborder="0" src="'.$link.'" target="_blank"></iframe>';
			setcookie('bfxdownload', null, time() - 3600, '/', 'borisfx.com');
		}
		else 
		{
			include_once ($_SERVER['DOCUMENT_ROOT'] .'/_functions/Salesforce/SalesforceManager.php');
			$did = array();
			foreach ($download as $object)
			{
				if ($object['DID'] == $for)
				{
					include_once $_SERVER['DOCUMENT_ROOT'] . '/_functions/Downloads/lib/cachefly.php';
					$did['host'] = $object['host'];
					$did['product'] = $object['product'];
					$did['version'] = $object['version'];
					$did['type'] = $object['type'];
					$file_name = $object['file'];
					$object['signedURL'] ? $link = cacheflyprotectedurl($file_name, 24*60*60) : $link = 'http://cdn.borisfx.com/borisfx/store/' . $file_name;
					$info = 'Your ' . $object['product'] . ' download will begin shortly. If no download starts please click <a href="' . $link . '" id = "download">here</a>.<br />';
					if ($object['type'] == 'Demo')
					{
						$object['product'] == 'mocha Pro' ? $message = 'Enjoy your trial of Mocha Pro.' : $message = 'A Trial Install Code is required for installation. You will receive your Trial Install Code via e-mail.<br /> If you do not receive the e-mail within 5 minutes, please check your "spam" folder. If you still do not see the e-mail, then please <a href="mailto:support@borisfx.com?subject=Need%20Trial%20Install%20Code">contact us</a> and let us know. In your e-mail, please indicate which Trial Version(s) you downloaded.';
					}
					else if ($object['type'] == 'Update')
					{
						$message = '<br />To install your update, please uninstall your current version of ' . $object['product'] . ' and reinstall using the installer being downloaded and your ' . $object['product'] . ' serial number. For further questions please <a href="http://www.borisfx.com/support/open-a-case/" id="support">fill out a support form</a> and you will be contacted shortly.';
					}
					$downloadFrame = '<iframe width="1px" height="1px" frameborder="0" src="'.$link.'" target="_blank"></iframe>';
					setcookie('bfxdownload', null, time() - 3600, '/', 'borisfx.com');
					break;
				}
			}
			
			if ($did['type'] == 'Demo')
			{
				$contactId = $user['sf_id'];
				$isLead = $user['lead'];
				
				$campaignId = '';
				$BCCAE = '@@@';
				$BCCAVX = '@@@';
				$BCCFxPlug = '@@@';
				$BCCSony = '@@@';
				$BCCResolve = '@@@';
				$Unit = '@@@';
				$FECAE = '@@@';
				$FECAVX = '@@@';
				$Mocha = '@@@';
				$RED = '@@@';
				$FX = '@@@';
				$Graffiti = '@@@';
				$Soundbite = '@@@';
				
				switch (true)
				{
					case stripos($did['product'], 'Continuum Complete') !== false:
						switch (true)
						{
							case stripos($did['host'], 'Adobe') !== false:
								$campaignId = $BCCAE;
								break;
							case stripos($did['host'], 'Avid') !== false:
								$campaignId = $BCCAVX;
								break;
							case stripos($did['host'], 'Apple') !== false:
								$campaignId = $BCCFxPlug;
								break;
							case stripos($did['host'], 'Sony') !== false:
								$campaignId = $BCCSony;
								break;
							case stripos($did['host'], 'Resolve') !== false:
								$campaignId = $BCCResolve;
								break;
						}
						break;
					case stripos($did['product'], 'Continuum Units') !== false:
						$campaignId = $Unit;
						break;
					case stripos($did['product'], 'Final Effects Complete') !== false:
						switch (true)
						{
							case stripos($did['host'], 'Adobe') !== false:
								$campaignId = $FECAE;
								break;
							case stripos($did['host'], 'Avid') !== false:
								$campaignId = $FECAVX;
								break;
						}
						break;
					case stripos($did['product'], 'Mocha') !== false:
						$campaignId = $Mocha;
						break;
					case stripos($did['product'], 'RED') !== false:
						$campaignId = $RED;
						break;
					case stripos($did['product'], 'FX') !== false:
						$campaignId = $FX;
						break;
					case stripos($did['product'], 'Graffiti') !== false:
						$campaignId = $Graffiti;
						break;
					case stripos($did['product'], 'Soundbite') !== false:
						$campaignId = $Soundbite;
						break;
				}
				
				
				if (!empty($campaignId))
				{
					$sfManager = new SalesforceManager();
					$existingMember = $sfManager->searchCampaignMember($campaignId, $contactId, $isLead);
					if ($existingMember)
					{
						$id = array();
						array_push($id, $existingMember[0]->Id);
						$deleteMember = $sfManager->mySforceConnection->delete($id);
						$sfManager->createCampaignMember($campaignId, $contactId, $isLead);
					}
					else
					{
						$sfManager->createCampaignMember($campaignId, $contactId, $isLead);
					}
				}
				if ($isLead)
				{
					$leadArray = array();
					$sf_updateLead =new stdClass;
					$sf_updateLead->Id = $contactId;
					$sf_updateLead->Product_Interest__c = $did['product'];
					$sf_updateLead->PrimaryVFXSoftware__c = $did['host'];
					array_push($leadArray, $sf_updateLead);
					$sfManager->mySforceConnection->update($leadArray, 'Lead');
				}
				else
				{
					$sf_contact_query = "SELECT Id, Product_Interest__c FROM Contact WHERE Id = '".$contactId."'";
					$sf_query_result = $sfManager->mySforceConnection->query($sf_contact_query);
					if ($sf_query_result->size >= 1)
					{
						if (strpos(strtolower($sf_query_result->records['0']->Product_Interest__c), strtolower($did['product'])) === false)
						{
							$did['product'] = $sf_query_result->records['0']->Product_Interest__c . ';' . $did['product'];
						}
						else
						{
							$did['product'] = $sf_query_result->records['0']->Product_Interest__c;
						}
					}
					$contactArray = array();
					$sf_updateContact = new stdClass;
					$sf_updateContact->Id = $contactId;
					$sf_updateContact->Product_Interest__c = $did['product'];
					$sf_updateContact->PrimaryVFXSoftware__c = $did['host'];
					array_push($contactArray, $sf_updateContact);
					$sfManager->mySforceConnection->update($contactArray, 'Contact');
				}
			}
		}
	}
	else if ($thanksType == 'signup')
	{
		$info = 'Thank you for signing up to the Boris FX newsletter! ';
		$message = 'Get ready for tutorials, training events, freebies, and more.';
	}
	else
	{
		$thanksType = 'support';
		$for = stripslashes($for);
		$for = str_replace('"', "", $for);
		$info = 'Your case #' . $for . ' has been submitted, our support team will be in touch shortly. ';
		$message = 'For tutorials and training please visit our <a href="http://www.borisfx.com/training/videos/">Boris TV</a> episode library.';
	}
}
else
{
	Header('Location: /support/downloads/');
	exit();
}
get_header(); ?>

<div class="page-container index">
    <div class="content">
        <div class="page-title-container">
            <div class="container">
                <div class="row">
                    <div class="col-xs-12">
                        <?php $post = $posts[0]; // Hack. Set $post so that the_date() works. ?>
                        <?php /* If this is a category archive */ if (is_category()) { ?>
                            <h1 class="page-title">Category of <?php single_cat_title(); ?></h1>
                            <p><?php echo category_description( $category ); ?></p>
                            <?php /* If this is a tag archive */ } elseif( is_tag() ) { ?>
                            <h1 class="page-title">Posts Tagged &#8216;<?php single_tag_title(); ?>&#8217;</h1>
                            <?php /* If this is a daily archive */ } elseif (is_day()) { ?>
                            <h1 class="page-title">Archive for <?php the_time('F jS, Y'); ?></h1>
                            <?php /* If this is a monthly archive */ } elseif (is_month()) { ?>
                            <h1 class="page-title">Archive for <?php the_time('F, Y'); ?></h1>
                            <?php /* If this is a yearly archive */ } elseif (is_year()) { ?>
                            <h1 class="page-title">Archive for <?php the_time('Y'); ?></h1>
                            <?php /* If this is an author archive */ } elseif (is_author()) { ?>
                            <h1 class="page-title">Author Archive</h1>
                        <?php } elseif (is_404()) { ?>
                            <h1 class="page-title">Error 404 - Page not found...</h1>
                        <?php } elseif (is_search()) { ?>
                            <h1 class="page-title">You are searching for "<?php echo $_GET["s"]; ?>".</h1>
                            <?php /* If this is a paged archive */ } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
                            <h1 class="page-title">Blog Archives</h1>
                        <?php } ?>

                        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>

                        <?php global $post; $current_post_id = $post->ID; ?>
                        <?php $top_parent_id = getTopParentPostID( $current_post_id ); ?>
                        <h1 class="page-title"><?php $alt_h2 = get_post_meta($post->ID, "alternate_title", true); if ($alt_h2 != ''){ echo $alt_h2; }else{ the_title(); } ?></h1>
                    </div>
                </div>
            </div>
        </div>
        <div class="content-container">
            <div class="container">
                <div class="row">
                    <div class="col-sm-9 maincol">

                        <div class="post" id="post-<?php the_ID(); ?>">

                            <div class="post-bg" style=" <?php $hide_content_box = get_post_meta($post->ID, "hide_content_box", true); if ($hide_content_box){ echo 'display:none;'; } ?> ">

                                <?php if ( is_page() ) { } else { ?>
                                    <p>
                                        <span class="meta"><?php if($theme_author=="Enable") { ?> <?php the_author(); ?> | <?php } the_date('','',''); ?> in <?php if ( is_page() ) { echo "A Page"; } else { ?> <?php the_category(',') ?> <?php }?></span>
                                        <?php the_tags(__('<span class="meta">Tags: '), ', ', '</span>'); ?>
                                    </p>
                                <?php }?>

                                <?php edit_post_link(__('edit'), '<div class="edit">', '</div>'); ?>

                                <div class="storycontent">
                                    <?php the_content(__('(more...)')); ?>
																		<?php echo($info); ?>
																		<?php echo($message);?>
																		<?php echo($downloadFrame);?>
                                </div>

                                <div class="feedback">
                                    <?php wp_link_pages(); ?>
                                </div>
                            </div>
                        </div>

                        <?php //comments_template(); // Get wp-comments.php template ?>

                        <?php endwhile; else: ?>

                        <!-- Put your logic here and you can render whatever within this col-sm-9
                        and I'll rearrange when finished -->

                        <h1>Thank you!</h1>
                        <p><?php echo $info;?></p>
						<p><?php echo $message;?></p>
						<div><?php if ($downloadFrame) echo $downloadFrame;?></div>

                    </div>
                </div>
            </div>
        </div>
        <div class="content-container">
            <div class="container">
                <div class="row">
                    <div class="col-sm-9 maincol">
                        <div class="post">
                            <h3 class="info"><?php _e('Sorry, no posts matched your criteria. You might want to search (again)?'); ?></h3>
                        </div>
                        <?php endif; ?>

                        <?php if (is_single() || is_page()) {} else { ?>
                            <p class="nav_link">
                                <?php
                                global $wp_query;

                                $big = 999999999; // need an unlikely integer

                                echo paginate_links( array(
                                    'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
                                    'format' => '?paged=%#%',
                                    'current' => max( 1, get_query_var('paged') ),
                                    'total' => $wp_query->max_num_pages
                                ) );
                                ?>
                            </p>
                        <?php } ?>

                        <div id="content-widgets" class="content-widgets">
                            <ul>
                                <?php if ( !function_exists('dynamic_sidebar')
                                    || !dynamic_sidebar('Standard Pages Below Content') ) : ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="col-sm-3 sidebarcol">
                        <div class="sidebar-div">
                            <?php
                            $depth = '';
                            $sidebar_hide_third_level = get_post_meta($post->ID, "sidebar_hide_third_level", true);
                            if ($sidebar_hide_third_level){
                                $depth = '&depth=1';
                            }

                            if ( (get_children($top_parent_id)) && !is_single() && $top_parent_id != 0 ) { ?>
                                <h3><?php echo get_the_title($top_parent_id); ?> </h3>
                                <div class="sidebar-navbox">
                                    <div class="sn-header"></div>
                                    <div class="sn-bg">
                                        <ul><?php wp_list_pages('title_li=&exclude=2060&sort_column=menu_order'.$depth.'&child_of='.$top_parent_id); ?></ul>
                                    </div>
                                    <div class="sn-footer"></div>
                                </div>
                            <?php } ?>
                            <?php get_sidebar(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'inc-testimonials.php'; ?>
<?php include 'inc-newsletter.php'; ?>
<?php get_footer(); ?>
