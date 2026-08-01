<?php

class SiteSetting extends Model
{
    public function getFirst()
    {
        $stmt = $this->db->prepare("SELECT * FROM site_settings LIMIT 1");
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }

    public function create($data, $whiteLogoInput = 'white_logo', $darkLogoInput = 'dark_logo')
    {
        $white_logo = null;
        $dark_logo  = null;

        if (!empty($_FILES[$whiteLogoInput]['name'])) {
            $white_logo = uploadImage($whiteLogoInput, 'settings', 'original');
        }

        if (!empty($_FILES[$darkLogoInput]['name'])) {
            $dark_logo = uploadImage($darkLogoInput, 'settings', 'original');
        }

        $stmt = $this->db->prepare("
        INSERT INTO site_settings (
            website_title,
            white_logo,
            dark_logo,
            phone,
            alt_phone,
            email,
            alt_email,
            address,
            footer_text,
            copyright,
            facebook_link,
            messenger_link,
            instagram_link,
            tiktok_link,
            x_link,
            youtube_link,
            client_declaration,
            header_code,
            body_code,
            created_by
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        if (!$stmt) {
            error_log("SiteSetting create prepare failed: " . $this->db->error);
            return false;
        }

        $websiteTitle       = $data['website_title'] ?? '';
        $phone              = $data['phone'] ?? '';
        $altPhone           = $data['alt_phone'] ?? '';
        $email              = $data['email'] ?? '';
        $altEmail           = $data['alt_email'] ?? '';
        $address            = $data['address'] ?? '';
        $footerText         = $data['footer_text'] ?? '';
        $copyright          = $data['copyright'] ?? '';
        $facebookLink       = $data['facebook_link'] ?? '';
        $messengerLink      = $data['messenger_link'] ?? '';
        $instagramLink      = $data['instagram_link'] ?? '';
        $tiktokLink         = $data['tiktok_link'] ?? '';
        $xLink              = $data['x_link'] ?? '';
        $youtubeLink        = $data['youtube_link'] ?? '';
        $clientDeclaration  = $data['client_declaration'] ?? '';
        $headerCode         = $data['header_code'] ?? '';
        $bodyCode           = $data['body_code'] ?? '';
        $createdBy          = !empty($data['created_by']) ? (int)$data['created_by'] : null;

        $stmt->bind_param(
            "sssssssssssssssssssi",
            $websiteTitle,
            $white_logo,
            $dark_logo,
            $phone,
            $altPhone,
            $email,
            $altEmail,
            $address,
            $footerText,
            $copyright,
            $facebookLink,
            $messengerLink,
            $instagramLink,
            $tiktokLink,
            $xLink,
            $youtubeLink,
            $clientDeclaration,
            $headerCode,
            $bodyCode,
            $createdBy
        );

        return $stmt->execute();
    }

    public function update($id, $data, $whiteLogoInput = 'white_logo', $darkLogoInput = 'dark_logo')
    {
        $idInt = (int)$id;
        $existing = $this->getById($idInt);

        // Default old logos
        $white_logo = $existing['white_logo'] ?? null;
        $dark_logo  = $existing['dark_logo'] ?? null;

        // New white logo
        if (!empty($_FILES[$whiteLogoInput]['name'])) {
            $white_logo = uploadImage($whiteLogoInput, 'settings', 'original');
        }

        // New dark logo
        if (!empty($_FILES[$darkLogoInput]['name'])) {
            $dark_logo = uploadImage($darkLogoInput, 'settings', 'original');
        }

        $stmt = $this->db->prepare("
        UPDATE site_settings SET
            website_title=?,
            white_logo=?,
            dark_logo=?,
            phone=?,
            alt_phone=?,
            email=?,
            alt_email=?,
            address=?,
            footer_text=?,
            copyright=?,
            facebook_link=?,
            messenger_link=?,
            instagram_link=?,
            tiktok_link=?,
            x_link=?,
            youtube_link=?,
            client_declaration=?,
            header_code=?,
            body_code=?,
            created_by=?
        WHERE id=?
        ");

        if (!$stmt) {
            error_log("SiteSetting update prepare failed: " . $this->db->error);
            return false;
        }

        $websiteTitle       = $data['website_title'] ?? '';
        $phone              = $data['phone'] ?? '';
        $altPhone           = $data['alt_phone'] ?? '';
        $email              = $data['email'] ?? '';
        $altEmail           = $data['alt_email'] ?? '';
        $address            = $data['address'] ?? '';
        $footerText         = $data['footer_text'] ?? '';
        $copyright          = $data['copyright'] ?? '';
        $facebookLink       = $data['facebook_link'] ?? '';
        $messengerLink      = $data['messenger_link'] ?? '';
        $instagramLink      = $data['instagram_link'] ?? '';
        $tiktokLink         = $data['tiktok_link'] ?? '';
        $xLink              = $data['x_link'] ?? '';
        $youtubeLink        = $data['youtube_link'] ?? '';
        $clientDeclaration  = $data['client_declaration'] ?? '';
        $headerCode         = $data['header_code'] ?? '';
        $bodyCode           = $data['body_code'] ?? '';
        $createdBy          = !empty($data['created_by']) ? (int)$data['created_by'] : null;

        $stmt->bind_param(
            "sssssssssssssssssssii",
            $websiteTitle,
            $white_logo,
            $dark_logo,
            $phone,
            $altPhone,
            $email,
            $altEmail,
            $address,
            $footerText,
            $copyright,
            $facebookLink,
            $messengerLink,
            $instagramLink,
            $tiktokLink,
            $xLink,
            $youtubeLink,
            $clientDeclaration,
            $headerCode,
            $bodyCode,
            $createdBy,
            $idInt
        );

        return $stmt->execute();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM site_settings WHERE id=? LIMIT 1");
        $idInt = (int)$id;
        $stmt->bind_param("i", $idInt);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }
}
