<?php

class SiteSetting extends Model
{
    public function getFirst()
    {
        $stmt = $this->db->prepare("SELECT * FROM site_settings LIMIT 1");
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
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

        $stmt->bind_param(
            "ssssssssssssssssssss",
            $data['website_title'] ?? '',
            $white_logo,
            $dark_logo,
            $data['phone'] ?? '',
            $data['alt_phone'] ?? '',
            $data['email'] ?? '',
            $data['alt_email'] ?? '',
            $data['address'] ?? '',
            $data['footer_text'] ?? '',
            $data['copyright'] ?? '',
            $data['facebook_link'] ?? '',
            $data['messenger_link'] ?? '',
            $data['instagram_link'] ?? '',
            $data['tiktok_link'] ?? '',
            $data['x_link'] ?? '',
            $data['youtube_link'] ?? '',
            $data['client_declaration'] ?? '',
            $data['header_code'] ?? '',
            $data['body_code'] ?? '',
            $data['created_by'] ?? null
        );

        return $stmt->execute();
    }

    public function update($id, $data, $whiteLogoInput = 'white_logo', $darkLogoInput = 'dark_logo')
    {
        $existing = $this->getById($id);

        // Default old logos
        $white_logo = $existing['white_logo'];
        $dark_logo  = $existing['dark_logo'];

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

        $stmt->bind_param(
            "sssssssssssssssssssii",
            $data['website_title'] ?? '',
            $white_logo,
            $dark_logo,
            $data['phone'] ?? '',
            $data['alt_phone'] ?? '',
            $data['email'] ?? '',
            $data['alt_email'] ?? '',
            $data['address'] ?? '',
            $data['footer_text'] ?? '',
            $data['copyright'] ?? '',
            $data['facebook_link'] ?? '',
            $data['messenger_link'] ?? '',
            $data['instagram_link'] ?? '',
            $data['tiktok_link'] ?? '',
            $data['x_link'] ?? '',
            $data['youtube_link'] ?? '',
            $data['client_declaration'] ?? '',
            $data['header_code'] ?? '',
            $data['body_code'] ?? '',
            $data['created_by'] ?? null,
            $id
        );

        return $stmt->execute();
    }


    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM site_settings WHERE id=? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
