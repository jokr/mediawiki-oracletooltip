<?php
$cardname = $_GET["name"];
$url = "http://gatherer.wizards.com/Pages/Card/Details.aspx";
try {
    // Fetch oracle
    $isSplit = strpos($cardname, '//');
    if ($isSplit) {
        $firstHalf = substr($cardname, 0, $isSplit - 1);
        $secondHalf = substr($cardname, $isSplit + 3);
        $oracle_page[] = curl_get($url, array("name" => $firstHalf, "part" => $firstHalf));
        $oracle_page[] = curl_get($url, array("name" => $secondHalf, "part" => $secondHalf));
    } else {
        $oracle_page[] = curl_get($url, array("name" => $cardname));
        //ugly check for flip cards, no better way found (yet)
        $isFlipCard = strpos($oracle_page[0], "cardComponent1\" class=\"cardComponentContainer\"><div");
        if($isFlipCard) {
            $oracle_page[] = substr($oracle_page[0], $isFlipCard);
            $oracle_page[0] = substr($oracle_page[0], 0, $isFlipCard);
        }
    }

    $card = extract_card($oracle_page);
} catch (Exception $e) {
    echo "<b>Error:</b> " . $e->getMessage();
    return;
}

display_tooltip($card);

function curl_get($url, array $get = NULL, array $options = array())
{
    $defaults = array(
        CURLOPT_URL => $url . (strpos($url, '?') === FALSE ? '?' : '') . http_build_query($get),
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 4
    );

    $ch = curl_init();
    curl_setopt_array($ch, ($options + $defaults));
    if (!$result = curl_exec($ch)) {
        trigger_error(curl_error($ch));
    }
    curl_close($ch);
    return $result;
}

function extract_card(array $input)
{
    $result = array();

    foreach ($input as $html_content) {
        $posName = strpos($html_content, "Card Name:");

        if (!$posName) {
            throw new Exception("No card with this name found.");
        }

        $posMana = strpos($html_content, "Mana Cost:");
        $posTypes = strpos($html_content, "Types:");
        $posText = strpos($html_content, "Card Text:");
        $posFlavor = strpos($html_content, "Flavor Text:");
        $posPT = strpos($html_content, "P/T:");
        $posLoyalty = strpos($html_content, "Loyalty:");
        $posExp = strpos($html_content, "Expansion:");

        $card['name'] = extract_name($html_content, $posName, $posMana, $posTypes);
        $card['mana'] = extract_mana($html_content, $posMana);

        $card['types'] = extract_types($html_content, $posTypes, $posText, $posFlavor, $posPT);
        $card['text'] = extract_text($html_content, $posText, $posFlavor, $posPT, $posLoyalty, $posExp);
        $card['pt'] = extract_pt($html_content, $posPT, $posExp);
        $card['loyalty'] = extract_loyalty($html_content, $posLoyalty, $posExp);

        $result[] = $card;
    }

    return $result;
}

function extract_name($html_content, $posName, $posMana, $posTypes)
{
    if ($posMana) {
        $name = substr($html_content, $posName, $posMana - $posName);
    } else {
        $name = substr($html_content, $posName, $posTypes - $posName);
    }
    return trim(str_replace('Card Name:', '', strip_tags($name)));
}

function extract_mana($html_content, $posMana)
{
    if (!$posMana) {
        return null;
    }

    $posCmc = strpos($html_content, "Converted Mana Cost:");
    $mana = substr($html_content, $posMana, $posCmc - $posMana);
    $mana = str_replace('medium', 'small', $mana);
    $mana = str_replace('src="', 'src="http://gatherer.wizards.com', $mana);
    $mana = trim(str_replace("Mana Cost:", "", strip_tags($mana, "<img>")));
    return $mana;
}

function extract_types($html_content, $posTypes, $posText, $posFlavor, $posPT)
{
    if ($posText) {
        $endTypes = $posText - $posTypes;
    } else {
        // no rules text
        if ($posFlavor) {
            $endTypes = $posFlavor - $posTypes;
        } else {
            // no flavor Text
            $endTypes = $posPT - $posTypes;
        }
    }

    $types = strip_tags(substr($html_content, $posTypes, $endTypes));
    $types = trim(str_replace("Types:", "", $types));
    return $types;
}

function extract_text($html_content, $posText, $posFlavor, $posPT, $posLoyalty, $posExp)
{
    if (!$posText) {
        return null;
    }
    if ($posFlavor) {
        $endText = $posFlavor - $posText;
    } else if ($posPT) {
        $endText = $posPT - $posText;
    } else if ($posLoyalty) {
        $endText = $posLoyalty - $posText;
    } else {
        $endText = $posExp - $posText;
    }

    $text = substr($html_content, $posText, $endText);
    $text = str_replace('src="', 'src="http://gatherer.wizards.com', $text);
    $text = str_replace('<div class="cardtextbox">', '<br/>', $text);
    $text = strip_tags($text, "<img><br>");
    $text = trim(str_replace("Card Text:", "", $text));
    $text = substr($text, 5); // to remove the first <br/> tag
    return $text;
}

function extract_pt($html_content, $posPT, $posExp)
{
    if (!$posPT) {
        return null;
    }

    $endPT = $posExp - $posPT;
    $PT = strip_tags(substr($html_content, $posPT, $endPT));
    $PT = trim(str_replace("P/T:", "", $PT));
    return $PT;
}

function extract_loyalty($html_content, $posLoyalty, $posExp)
{
    if (!$posLoyalty) {
        return null;
    }

    $endLoyalty = $posExp - $posLoyalty;
    $loyalty = strip_tags(substr($html_content, $posLoyalty, $endLoyalty));
    $loyalty = trim(str_replace("Loyalty:", "", $loyalty));
    return $loyalty;
}

function display_tooltip($cards)
{
    foreach ($cards as $card) {
        echo "<span class=\"card\"><b>" . $card['name'] . "</b><br />";
        if ($card['mana']) {
            echo $card['mana'] . "<br />";
        }
        echo $card['types'] . "<br />";
        if ($card['text']) {
            echo "<span class=\"rules\">".$card['text'] . "</span><br />";
        }
        if ($card['pt']) {
            echo $card['pt'] . "<br />";
        }
        if ($card['loyalty']) {
            echo $card['loyalty'] . "<br />";
        }
        echo "</span>";
    }
}