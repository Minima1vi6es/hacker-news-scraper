<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

function fetchHackerNews() {
    $url = "https://hacker-news.firebaseio.com/v0/topstories.json";
    $topStories = json_decode(@file_get_contents($url), true);
    $newsItems = [];

    if ($topStories) {
        foreach (array_slice($topStories, 0, 10) as $storyId) {
            $storyUrl = "https://hacker-news.firebaseio.com/v0/item/$storyId.json";
            $storyData = json_decode(@file_get_contents($storyUrl), true);
            if (isset($storyData['title']) && isset($storyData['url'])) {
                $newsItems[] = [
                    'title' => $storyData['title'],
                    'url' => $storyData['url'],
                    'date' => date('r'), // No date available from API, using current timestamp
                    'source' => 'Hacker News'
                ];
            }
        }
    }
    return $newsItems;
}

function fetchRSSFeeds() {
    $rssFeeds = [
        'The Hacker News' => 'https://feeds.feedburner.com/TheHackersNews',
        'Graham Cluley' => 'https://grahamcluley.com/feed/',
        'Schneier on Security' => 'https://www.schneier.com/blog/atom.xml',
        'Krebs on Security' => 'https://krebsonsecurity.com/feed/',
        'Dark Reading' => 'https://www.darkreading.com/rss/all.xml',
        'Troy Hunt' => 'https://www.troyhunt.com/rss/',
        'WeLiveSecurity' => 'https://www.welivesecurity.com/feed/',
        'Sophos News' => 'https://news.sophos.com/en-us/feed/',
        'Infosecurity Magazine' => 'https://www.infosecurity-magazine.com/rss/news/',
        'Reddit: r/cybersecurity' => 'https://www.reddit.com/r/cybersecurity/.rss'
    ];

    $newsItems = [];

    foreach ($rssFeeds as $source => $url) {
        $rss = @simplexml_load_file($url);
        if (!$rss || !isset($rss->channel->item)) {
            error_log("Failed to load RSS feed: $source ($url)");
            continue;
        }

        foreach ($rss->channel->item as $item) {
            $newsItems[] = [
                'title' => (string) $item->title,
                'url' => (string) $item->link,
                'date' => isset($item->pubDate) ? (string) $item->pubDate : date('r'),
                'source' => $source
            ];
        }
    }
    return $newsItems;
}

// Fetch news from all sources
$newsList = array_merge(fetchHackerNews(), fetchRSSFeeds());

// Sort news items by date (most recent first)
usort($newsList, function ($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cybersecurity News Aggregator</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Latest Cybersecurity News</h1>
    <ul>
        <?php if (empty($newsList)): ?>
            <li>No news available. Please check back later.</li>
        <?php else: ?>
            <?php foreach ($newsList as $news): ?>
                <li>
                    <a href="<?= htmlspecialchars($news['url']); ?>" target="_blank"><?= htmlspecialchars($news['title']); ?></a>
                    <br>
                    <small>Source: <?= htmlspecialchars($news['source']); ?> | Published on: <?= date('F j, Y, g:i a', strtotime($news['date'])); ?></small>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</body>
</html>
