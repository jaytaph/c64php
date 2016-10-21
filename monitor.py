import pygame
import sys
import sysv_ipc
import time
import numpy as np

"""
Simple monitoring script. Uses Pygame ("pip install pygame"). Maybe we can use PHP-GTK or something. But this
will do for now...
"""


# Zero out all keys
keyboard_row_bits = np.zeros(8, dtype='uint8')
keyboard_col_bits = np.zeros(8, dtype='uint8')

def swaprgb(c):
    """
    Swaps RGB values from hex ("0xRRGGBB" becomes "0xBBGGRR")
    """
    s = c[0:2] + c[6:8] + c[4:6] + c[2:4]
    return s


def write_key(event, key):
    global keyboard_col_bits
    global keyboard_row_bits

    """
    Write the given key to the keyboard matrix and shm
    """
    for (row,col),value in np.ndenumerate(keyboard_matrix):
        if keyboard_matrix[row][col] == key:
            if event == pygame.KEYDOWN:
                keyboard_col_bits[col] += 1
                keyboard_row_bits[row] += 1
            else:
                keyboard_col_bits[col] -= 1
                keyboard_row_bits[row] -= 1

    # Note that we increase the col and row bits, and not set them to 0 or 1. This is because
    # we could press two keys on the same row. If so, it would return the row back to
    # zero when we release one of the keys, while that row should stay high for the other still
    # pressed key. The numpy.packbits() will store everything above 1 as a 1, so it will pack
    # the correct values.

    packed_cols = np.packbits(keyboard_col_bits)
    packed_rows = np.packbits(keyboard_row_bits)

    # Write the row/col bytes at the end of the monitor buffer
    smh.write(packed_cols[0], 117384)
    smh.write(packed_rows[0], 117384 + 1)


def update_screen():
        """
        Updates the screen with the contents of the shared memory
        """
        # Fetch color numbers from shared memory
        buf = smh.read(292 * 402, 2)

        # Place colors on monitor screen
        for y in xrange(292):
            for x in xrange(402):
                c = ord(buf[y * 402 + x]) & 15
                pixels[x][y] = c64colors[c]

        pygame.display.flip()



# Keyboard mapping from pygame keys to C64 keys
keyboard_matrix = [
    [ pygame.K_ESCAPE, pygame.K_q,      pygame.K_TAB,    pygame.K_SPACE,  pygame.K_2,     pygame.K_LCTRL,     pygame.K_BACKSPACE, pygame.K_1 ],
    [ pygame.K_SLASH,  pygame.K_CARET,  pygame.K_EQUALS, pygame.K_RSHIFT, pygame.K_HOME,  pygame.K_SEMICOLON, pygame.K_ASTERISK,  pygame.K_DOLLAR, ],
    [ pygame.K_COMMA,  pygame.K_AT,     pygame.K_COLON,  pygame.K_PERIOD, pygame.K_MINUS, pygame.K_l,         pygame.K_p,         pygame.K_PLUS, ],
    [ pygame.K_n,      pygame.K_o,      pygame.K_k,      pygame.K_m,      pygame.K_0,     pygame.K_j,         pygame.K_i,         pygame.K_9, ],
    [ pygame.K_v,      pygame.K_u,      pygame.K_h,      pygame.K_b,      pygame.K_8,     pygame.K_g,         pygame.K_y,         pygame.K_7, ],
    [ pygame.K_x,      pygame.K_t,      pygame.K_f,      pygame.K_c,      pygame.K_6,     pygame.K_d,         pygame.K_r,         pygame.K_5, ],
    [ pygame.K_LSHIFT, pygame.K_e,      pygame.K_s,      pygame.K_z,      pygame.K_4,     pygame.K_a,         pygame.K_w,         pygame.K_3, ],
    [ pygame.K_DOWN,   pygame.K_F5,     pygame.K_F3,     pygame.K_F1,     pygame.K_F7,    pygame.K_RALT,      pygame.K_RETURN,    pygame.K_DELETE, ],
]

# Defined C64 colors
c64colors = [
    pygame.Color(swaprgb("0x000000")),   # 0
    pygame.Color(swaprgb("0xFFFFFF")),
    pygame.Color(swaprgb("0x68372b")),
    pygame.Color(swaprgb("0x70a4b2")),
    pygame.Color(swaprgb("0x6f3d86")),
    pygame.Color(swaprgb("0x588d43")),
    pygame.Color(swaprgb("0x352879")),
    pygame.Color(swaprgb("0xb8c76f")),
    pygame.Color(swaprgb("0x6f4f25")),   # 8
    pygame.Color(swaprgb("0x433900")),
    pygame.Color(swaprgb("0x9a6759")),
    pygame.Color(swaprgb("0x444444")),
    pygame.Color(swaprgb("0x6c6c6c")),
    pygame.Color(swaprgb("0x9ad284")),
    pygame.Color(swaprgb("0x6c5eB5")),
    pygame.Color(swaprgb("0x959595")),   # 15
]



if __name__ == "__main__":
    # SHM key as used in the ShmIo class
    SHM_KEY = 0x6303b5eb

    # Open up SHM.
    smh = sysv_ipc.SharedMemory(SHM_KEY)

    # Init monitor
    pygame.init()
    screen = pygame.display.set_mode( (402,292) )
    pixels = pygame.surfarray.pixels2d(screen)
    pygame.display.set_caption("C64 Monitor")

    cnt = 0
    while True:
        for event in pygame.event.get():
            if event.type == pygame.KEYDOWN or event.type == pygame.KEYUP:
                write_key(event.type, event.key)
            if event.type == pygame.QUIT:
                pygame.quit()
                sys.exit()

        # Sleep a little bit
        time.sleep(0.05)

        # See if we are about to refresh the screen
        cnt += 1
        if cnt == 10 :
            cnt = 0
            update_screen()

