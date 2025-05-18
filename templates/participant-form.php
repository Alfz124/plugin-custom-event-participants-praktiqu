<div class="participant-form">
    <?php
    // Check if the participant data is set
    if (isset($participant_data) && is_array($participant_data)) {
        foreach ($participant_data as $index => $participant) {
            echo "<h3>Data Peserta " . ($index + 1) . "</h3>";

            woocommerce_form_field("peserta_{$index}_nama_depan", [
                'type' => 'text',
                'class' => ['form-row-first'],
                'label' => 'Nama Depan',
                'required' => true,
            ], isset($participant['nama_depan']) ? $participant['nama_depan'] : '');

            woocommerce_form_field("peserta_{$index}_nama_belakang", [
                'type' => 'text',
                'class' => ['form-row-last'],
                'label' => 'Nama Belakang',
                'required' => true,
            ], isset($participant['nama_belakang']) ? $participant['nama_belakang'] : '');

            woocommerce_form_field("peserta_{$index}_pekerjaan", [
                'type' => 'text',
                'class' => ['form-row-wide'],
                'label' => 'Pekerjaan',
            ], isset($participant['pekerjaan']) ? $participant['pekerjaan'] : '');

            woocommerce_form_field("peserta_{$index}_kota", [
                'type' => 'text',
                'class' => ['form-row-wide'],
                'label' => 'Kota',
            ], isset($participant['kota']) ? $participant['kota'] : '');

            woocommerce_form_field("peserta_{$index}_telepon", [
                'type' => 'tel',
                'class' => ['form-row-wide'],
                'label' => 'Nomor Telepon',
                'required' => true,
            ], isset($participant['telepon']) ? $participant['telepon'] : '');

            woocommerce_form_field("peserta_{$index}_email", [
                'type' => 'email',
                'class' => ['form-row-wide'],
                'label' => 'Email',
                'required' => true,
            ], isset($participant['email']) ? $participant['email'] : '');
        }
    }
    ?>
</div>