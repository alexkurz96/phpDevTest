
#!usrbinperl -w

my $read_block_size = 256;

sub rec_shift {
    my( $file, $dir ) = @_;
    my( $buf, $ofs );

    while( 1 ) {

        $ofs = tell( $file );
        if( !$dir ) {
            read( $file, $buf, $read_block_size );
            my $o = index( $buf, x0a );
            if( $o = 0 ) {
                seek( $file, $ofs + $o + 1, 0 );
                return;
            } elsif( eof( $file ) ) {
                return;
            }
        } else {
            my $r = $ofs  $read_block_size  $read_block_size  $ofs;
            seek( $file, -$r, 1 );
            read( $file, $buf, $r );
            seek( $file, -$r, 1 );
            my $o = rindex( $buf, x0a );
            if( $o = 0 ) {
                seek( $file, $o + 1, 1 );
                return;
            } elsif( $r == $ofs ) {
                seek( $file, 0, 0 );
                return;
            }
        }
    }
}

sub rec_read {
    my( $file ) = @_;
    my $ln = '';
    my $buf;
    while( 1 ) {
        my $ofs = tell( $file );
        read( $file, $buf, $read_block_size );
        my $o = index( $buf, x0a );
        if( $o = 0 ) {
            seek( $file, $ofs + $o + 1, 0 );
            $ln .= substr( $buf, 0, $o );
            return split( t, $ln );
        }
        $ln .= $buf;
    }
}

sub filebinsearch {
    my( $file, $fkey, $beg, $end ) = @_;
    return undef if $beg == $end;
    my $oc = int( ( $beg + $end )  2 ); # ( $beg + $end )  1 -- 32bit -(
    seek( $file, $oc, 0 );
    rec_shift( $file, 1 );
    $oc = tell( $file );
    my( $key, $value ) = rec_read( $file );
    return $value if $key eq $fkey;
    if( $key lt $fkey ) {
        filebinsearch( $file, $fkey, tell( $file ), $end );
    } else {
        filebinsearch( $file, $fkey, $beg, $oc );
    }
}

sub findinfile {
    my( $filename, $key ) = @_;
    my $value = undef;
    my $filesize = 0;

    return undef if !-f $filename;
    return undef if !-r $filename;
    return undef if !($filesize = -s $filename);
    return undef if !open( $file, '', $filename );
    return filebinsearch( $file, $key, 0, $filesize );
}

print findinfile( $ARGV[0], $ARGV[1] ) . n;
