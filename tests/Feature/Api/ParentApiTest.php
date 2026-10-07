<?php

it('allows parent to view their child attendance', function () {
    $ortu = actingAsRole('Orang Tua');
    $siswa = userWithRole('Siswa', ['parent_id' => $ortu->id]);

    $this->getJson("/api/parent/children/{$siswa->id}/attendances")
         ->assertOk();
});

it('prevents parent from viewing other people child attendance', function () {
    actingAsRole('Orang Tua');
    
    // Anak ini milik orang tua lain (parent_id berbeda)
    $ortuLain = userWithRole('Orang Tua');
    $anakOrangLain = userWithRole('Siswa', ['parent_id' => $ortuLain->id]);

    $this->getJson("/api/parent/children/{$anakOrangLain->id}/attendances")
         ->assertNotFound(); // TES NEGATIF (Disembunyikan oleh sistem)
});
